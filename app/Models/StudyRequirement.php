<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\RequirementConnected;

class StudyRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_id',
        'user_id',
        'contact_role',
        'contact_name',
        'contact_email',
        'contact_phone',
        'is_contact_verified',
        'verified_at',
        'student_name',
        'student_grade',
        'subjects',
        'learning_mode',
        'preferred_days',
        'preferred_time',
        'location_city',
        'location_state',
        'location_area',
        'location_pincode',
        'budget_min',
        'budget_max',
        'requirements',
        'status',
        'meta',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'subjects' => 'array',
        'meta' => 'array',
        'verified_at' => 'datetime',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $requirement) {
            if (empty($requirement->reference_id)) {
                $requirement->reference_id = self::generateReferenceId();
            }
        });
    }

    public static function generateReferenceId(): string
    {
        $prefix = 'REQ';
        $datePart = now()->format('Ymd');

        do {
            $sequence = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $candidate = "{$prefix}-{$datePart}-{$sequence}";
        } while (self::where('reference_id', $candidate)->exists());

        return $candidate;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connectedUsers(): HasMany
    {
        return $this->hasMany(RequirementConnected::class, 'requirement_id');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}










