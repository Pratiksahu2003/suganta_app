<?php

namespace App\Models;

use App\Models\Concerns\FlushesDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use FlushesDashboardCache, HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'name',
        'email',
        'user_id',
        'lead_owner_id',
        'phone',
        'type',
        'source',
        'subject_interest',
        'grade_level',
        'location',
        'message',
        'status',
        'priority',
        'assigned_to',
        'last_contacted_at',
        'next_follow_up_at',
        'contact_history',
        'notes',
        'estimated_value',
        'utm_source',
        'utm_medium',
        'utm_campaign',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'contact_history' => 'array',
        'notes' => 'array',
        'estimated_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lead $lead) {
            if (empty($lead->lead_id)) {
                $lead->lead_id = static::generateLeadId();
            }
        });

        static::saved(function (Lead $lead): void {
            static::flushDashboardCacheForUser($lead->user_id);
            static::flushDashboardCacheForUser($lead->lead_owner_id);
            static::flushDashboardCacheForUser($lead->assigned_to);
        });

        static::deleted(function (Lead $lead): void {
            static::flushDashboardCacheForUser($lead->user_id);
            static::flushDashboardCacheForUser($lead->lead_owner_id);
            static::flushDashboardCacheForUser($lead->assigned_to);
        });

        static::restored(function (Lead $lead): void {
            static::flushDashboardCacheForUser($lead->user_id);
            static::flushDashboardCacheForUser($lead->lead_owner_id);
            static::flushDashboardCacheForUser($lead->assigned_to);
        });
    }

    public static function generateLeadId(): string
    {
        $prefix = 'SUG';
        $datePart = now()->format('Ymd');
        do {
            $seq = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $candidate = $prefix . '-' . $datePart . '-' . $seq;
        } while (static::withTrashed()->where('lead_id', $candidate)->exists());
        return $candidate;
    }

    /**
     * Get the status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'new' => 'badge bg-warning',
            'contacted' => 'badge bg-info',
            'qualified' => 'badge bg-success',
            'lost' => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Get the status display text
     */
    public function getStatusDisplayAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope for searching leads
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject_interest', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Get the user who created the lead
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the teacher who owns the lead (lead_owner_id)
     */
    public function leadOwner()
    {
        return $this->belongsTo(User::class, 'lead_owner_id');
    }

    /**
     * Get the assigned user for the lead
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope to get leads for a specific teacher
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('lead_owner_id', $teacherId);
    }

    /**
     * Scope to get leads assigned to a specific user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to get leads for authenticated user only:
     * - Leads owned by the user (lead_owner_id)
     * - Leads assigned to the user (assigned_to)
     * - Leads created by the user (user_id)
     */
    public function scopeForAuthUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('lead_owner_id', $userId)
                ->orWhere('assigned_to', $userId);
        });
    }

    /**
     * Get the profile of the user who created the lead
     */
    public function userProfile()
    {
        return $this->hasOneThrough(Profile::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }

    /**
     * Get the profile of the lead owner (teacher)
     */
    public function leadOwnerProfile()
    {
        return $this->hasOneThrough(Profile::class, User::class, 'id', 'user_id', 'lead_owner_id', 'id');
    }

    /**
     * Get the profile of the assigned user
     */
    public function assignedToProfile()
    {
        return $this->hasOneThrough(Profile::class, User::class, 'id', 'user_id', 'assigned_to', 'id');
    }
}
