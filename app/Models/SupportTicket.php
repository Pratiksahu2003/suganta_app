<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Traits\HasActivityNotifications;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes, HasActivityNotifications;

    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'priority',
        'status',
        'category',
        'assigned_to',
        'assigned_admin_id',
        'resolved_at',
        'admin_notes',
        'user_notes',
        'attachment_path',
        'ticket_number'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Status levels
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_WAITING_FOR_USER = 'waiting_for_user';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    // Categories
    const CATEGORY_TECHNICAL = 'technical';
    const CATEGORY_BILLING = 'billing';
    const CATEGORY_ACCOUNT = 'account';
    const CATEGORY_SUBJECT = 'subject';
    const CATEGORY_EXAM = 'exam';
    const CATEGORY_NEW_SUBJECT_REQUEST = 'new_subject_request';
    const CATEGORY_NEW_EXAM_REQUEST = 'new_exam_request';
    const CATEGORY_NEW_EXAM_CATEGORY_REQUEST = 'new_exam_category_request';
    const CATEGORY_FEATURE_REQUEST = 'feature_request';
    const CATEGORY_BUG_REPORT = 'bug_report';
    const CATEGORY_GENERAL = 'general';

    /**
     * Boot the model and generate ticket number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    /**
     * Generate unique ticket number
     */
    public static function generateTicketNumber()
    {
        $prefix = 'TKT';
        $year = date('Y');
        $month = date('m');
        
        // Get the last ticket number for this month
        $lastTicket = self::where('ticket_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('ticket_number', 'desc')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->ticket_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get priority options
     */
    public static function getPriorityOptions()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    /**
     * Get status options
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_WAITING_FOR_USER => 'Waiting for User',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /**
     * Get category options
     */
    public static function getCategoryOptions()
    {
        return [
            self::CATEGORY_TECHNICAL => 'Technical Issue',
            self::CATEGORY_BILLING => 'Billing & Payment',
            self::CATEGORY_ACCOUNT => 'Account Issue',
            self::CATEGORY_SUBJECT => 'Subject Related',
            self::CATEGORY_EXAM => 'Exam Related',
            self::CATEGORY_NEW_SUBJECT_REQUEST => 'Request New Subject',
            self::CATEGORY_NEW_EXAM_REQUEST => 'Request New Exam',
            self::CATEGORY_NEW_EXAM_CATEGORY_REQUEST => 'Request New Exam Category',
            self::CATEGORY_FEATURE_REQUEST => 'Feature Request',
            self::CATEGORY_BUG_REPORT => 'Bug Report',
            self::CATEGORY_GENERAL => 'General Inquiry',
        ];
    }

    /**
     * Get priority badge class
     */
    public function getPriorityBadgeClass()
    {
        return [
            self::PRIORITY_LOW => 'bg-secondary',
            self::PRIORITY_MEDIUM => 'bg-info',
            self::PRIORITY_HIGH => 'bg-warning',
            self::PRIORITY_URGENT => 'bg-danger',
        ][$this->priority] ?? 'bg-secondary';
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass()
    {
        return [
            self::STATUS_OPEN => 'bg-primary',
            self::STATUS_IN_PROGRESS => 'bg-warning',
            self::STATUS_WAITING_FOR_USER => 'bg-info',
            self::STATUS_RESOLVED => 'bg-success',
            self::STATUS_CLOSED => 'bg-secondary',
        ][$this->status] ?? 'bg-secondary';
    }

    /**
     * Check if ticket is resolved
     */
    public function isResolved()
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Check if ticket is open
     */
    public function isOpen()
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_WAITING_FOR_USER]);
    }

    /**
     * Get time since creation
     */
    public function getTimeSinceCreation()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get resolution time
     */
    public function getResolutionTime()
    {
        if (!$this->resolved_at) {
            return null;
        }
        return $this->created_at->diffInHours($this->resolved_at);
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }

    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class);
    }

    /**
     * Scope for open tickets
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_WAITING_FOR_USER]);
    }

    /**
     * Scope for resolved tickets
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Scope for high priority tickets
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope for tickets by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for tickets by user role
     */
    public function scopeByUserRole($query, $role)
    {
        return $query->whereHas('user', function ($q) use ($role) {
            $q->where('role', $role);
        });
    }

    /**
     * Check if user can access this ticket
     */
    public function canBeAccessedBy(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }

    /**
     * Check if user can reply to this ticket
     */
    public function canBeRepliedToBy(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }

    /**
     * Check if user can close this ticket
     */
    public function canBeClosedBy(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }

    /**
     * Check if user can assign this ticket
     */
    public function canBeAssignedBy(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Check if user can download attachments from this ticket
     */
    public function canDownloadAttachmentsBy(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }
} 