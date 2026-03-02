<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileStudentInfo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'profile_student_info';

    protected $fillable = [
        'profile_id',
        'student_id',
        'current_class_id',
        'current_school',
        'board_id',
        'stream_id',
        'subjects_of_interest',
        'learning_goals',
        'learning_mode',
        'budget_min',
        'budget_max',
        'preferred_timing',
        'learning_challenges',
        'special_requirements',
        'extracurricular_interests',
        'parent_name',
        'parent_phone',
        'parent_email',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
        'current_grades',
        'target_grades',
        'previous_tutoring_experience',
    ];

    protected $casts = [
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'subjects_of_interest' => 'array',
        'learning_goals' => 'array',
        'extracurricular_interests' => 'array',
        'current_grades' => 'array',
        'target_grades' => 'array',
        'previous_tutoring_experience' => 'boolean',
    ];

    /**
     * Get the profile that owns the student info
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the learning mode display name
     */
    public function getLearningModeNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('teaching_mode', $this->learning_mode);
    }

    /**
     * Get the current class display name
     */
    public function getCurrentClassNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('current_class', $this->current_class_id);
    }

    /**
     * Get the board display name
     */
    public function getBoardNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('board', $this->board_id);
    }

    /**
     * Get the stream display name
     */
    public function getStreamNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('stream', $this->stream_id);
    }

    /**
     * Get the budget range display
     */
    public function getBudgetRangeAttribute()
    {
        if ($this->budget_min && $this->budget_max) {
            return '₹' . number_format($this->budget_min) . ' - ₹' . number_format($this->budget_max);
        } elseif ($this->budget_min) {
            return '₹' . number_format($this->budget_min) . '+';
        } elseif ($this->budget_max) {
            return 'Up to ₹' . number_format($this->budget_max);
        }
        
        return 'Not specified';
    }
} 