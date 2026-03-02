<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'gender',
        'current_class',
        'school_name',
        'board',
        // Location fields
        'city',
        'state',
        'pincode',
        'area',
        'address',
        'latitude',
        'longitude',
        // Learning preferences
        'subjects_interested',
        'learning_goals',
        'preferred_learning_mode',
        'preferred_timings',
        'budget_min',
        'budget_max',
        'urgency',
        // Additional information
        'learning_challenges',
        'special_requirements',
        'extracurricular_interests',
        'parent_phone',
        'emergency_contact',
        // Progress tracking
        'current_grades',
        'target_grades',
        'previous_tutoring_experience',
        // Status
        'is_active',
        'profile_completed',
        'last_activity',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'subjects_interested' => 'array',
        'learning_goals' => 'array',
        'preferred_timings' => 'array',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'learning_challenges' => 'array',
        'extracurricular_interests' => 'array',
        'current_grades' => 'array',
        'target_grades' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'profile_completed' => 'boolean',
        'last_activity' => 'datetime',
    ];

    /**
     * Get the user that owns the student profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subjects that the student is interested in
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subjects', 'student_id', 'subject_id')
                    ->withPivot(['current_level', 'target_level', 'priority'])
                    ->withTimestamps();
    }

    /**
     * Get the teachers that the student has worked with
     */
    public function teachers()
    {
        return $this->belongsToMany(TeacherProfile::class, 'student_teachers', 'student_id', 'teacher_id')
                    ->withPivot(['subject_id', 'start_date', 'end_date', 'status', 'rating', 'review'])
                    ->withTimestamps();
    }

    /**
     * Get the branches that the student is enrolled in
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_student', 'student_id', 'branch_id')
                    ->withPivot(['enrollment_date', 'status'])
                    ->withTimestamps();
    }

    /**
     * Get the sessions that the student is enrolled in
     */
    public function sessions()
    {
        return $this->belongsToMany(TeacherSession::class, 'session_enrollments', 'student_id', 'session_id')
                    ->withPivot(['enrolled_at', 'status', 'payment_status', 'rating', 'review'])
                    ->withTimestamps();
    }

    /**
     * Get the student's bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'student_id');
    }

    /**
     * Get the student's favorites
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'user_id', 'user_id');
    }

    /**
     * Get the full address
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->area,
            $this->city,
            $this->state,
            $this->pincode
        ]);
        return implode(', ', $parts);
    }

    /**
     * Get age from date of birth
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Check if profile is complete
     */
    public function isProfileComplete()
    {
        return $this->profile_completed && 
               !empty($this->city) && 
               !empty($this->subjects_interested) && 
               !empty($this->preferred_learning_mode);
    }

    /**
     * Update last activity
     */
    public function updateActivity()
    {
        $this->update(['last_activity' => now()]);
    }

    /**
     * Scope for active students
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for students by location
     */
    public function scopeByLocation($query, $city = null, $state = null)
    {
        if ($city) {
            $query->where('city', 'like', "%{$city}%");
        }
        if ($state) {
            $query->where('state', 'like', "%{$state}%");
        }
        return $query;
    }

    /**
     * Scope for students by learning mode
     */
    public function scopeByLearningMode($query, $mode)
    {
        return $query->where('preferred_learning_mode', $mode);
    }

    /**
     * Scope for students by budget range
     */
    public function scopeByBudget($query, $minBudget = null, $maxBudget = null)
    {
        if ($minBudget) {
            $query->where('budget_max', '>=', $minBudget);
        }
        if ($maxBudget) {
            $query->where('budget_min', '<=', $maxBudget);
        }
        return $query;
    }
} 