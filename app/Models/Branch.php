<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\User;
use App\Models\Profile;

class Branch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'branches';

    protected $fillable = [
        'user_id',
        'branch_name',
        'branch_code',
        'branch_type',
        'address',
        'city',
        'state',
        'pincode',
        'contact_person',
        'contact_phone',
        'contact_email',
        'facilities',
        'capacity',
        'is_active',
        'branch_manager_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'facilities' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the profile (institute) that owns the branch through user
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'user_id', 'user_id');
    }
    
    /**
     * Get the user that owns the branch
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch manager
     */
    public function branchManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_manager_id');
    }

    /**
     * Get the teachers for this branch
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(TeacherProfile::class, 'branch_teacher', 'branch_id', 'teacher_id')
            ->withPivot(['joining_date', 'status', 'additional_data'])
            ->withTimestamps();
    }

    /**
     * Get the students for this branch
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'branch_student', 'branch_id', 'student_id')
            ->withPivot(['enrollment_date', 'status'])
            ->withTimestamps();
    }

    /**
     * Get the sessions for this branch
     */
    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\TeacherSession::class,
            \App\Models\TeacherProfile::class,
            'branch_id', // Foreign key on teacher_profiles table
            'teacher_profile_id', // Foreign key on teacher_sessions table
            'id', // Local key on branches table
            'id' // Local key on teacher_profiles table
        )->select(['teacher_sessions.*', 'teacher_profiles.branch_id']);
    }

    /**
     * Get the subjects offered at this branch
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'branch_subject');
    }

    /**
     * Get the reviews for this branch
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get the payments for this branch
     * TODO: Implement when Payment model is created
     */
    // public function payments(): HasMany
    // {
    //     return $this->hasMany(Payment::class);
    // }

    /**
     * Scope a query to only include active branches
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the full address
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->city}, {$this->state} - {$this->pincode}";
    }

    /**
     * Get the branch name (alias for branch_name)
     */
    public function getNameAttribute(): string
    {
        return $this->branch_name;
    }

    /**
     * Get the branch code (alias for branch_code)
     */
    public function getCodeAttribute(): string
    {
        return $this->branch_code;
    }

    /**
     * Get the student count
     */
    public function getStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Get the teacher count
     */
    public function getTeachersCountAttribute(): int
    {
        return $this->teachers()->count();
    }

    /**
     * Get the sessions count
     */
    public function getSessionsCountAttribute(): int
    {
        return 0;
    }

    /**
     * Get the revenue
     * TODO: Implement when Payment model is created
     */
    public function getRevenueAttribute(): float
    {
        // return $this->payments()->sum('amount');
        return 0.0; // Placeholder until Payment model is implemented
    }

    /**
     * Get the average rating
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
} 