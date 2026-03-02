<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'student_id',
        'teacher_id',
        'subject_id',
        'title',
        'description',
        'scheduled_at',
        'duration_minutes',
        'rate_per_hour',
        'total_amount',
        'status',
        'session_type',
        'meeting_link',
        'location',
        'notes',
        'cancellation_reason',
        'cancelled_at',
        'completed_at',
        'payment_details',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'payment_details' => 'array',
        'rate_per_hour' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_id)) {
                $booking->booking_id = 'BK' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the student that owns the booking
     */
    public function student()
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    /**
     * Get the teacher for the booking
     */
    public function teacher()
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    /**
     * Get the subject for the booking
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Scope to get bookings for a specific student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to get bookings for a specific teacher
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope to get bookings by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get upcoming bookings
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Scope to get past bookings
     */
    public function scopePast($query)
    {
        return $query->where('scheduled_at', '<', now())
                    ->whereIn('status', ['completed', 'cancelled', 'no_show']);
    }

    /**
     * Check if booking is upcoming
     */
    public function isUpcoming()
    {
        return $this->scheduled_at > now() && in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Check if booking is past
     */
    public function isPast()
    {
        return $this->scheduled_at < now() || in_array($this->status, ['completed', 'cancelled', 'no_show']);
    }

    /**
     * Check if booking can be cancelled
     */
    public function canBeCancelled()
    {
        return $this->status === 'pending' && $this->scheduled_at > now()->addHours(2);
    }

    /**
     * Check if booking can be rescheduled
     */
    public function canBeRescheduled()
    {
        return in_array($this->status, ['pending', 'confirmed']) && $this->scheduled_at > now()->addHours(2);
    }
}
