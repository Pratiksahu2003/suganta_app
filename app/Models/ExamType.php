<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamType extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_id',
        'name',
        'code',
        'duration',
        'questions_count',
        'passing_score',
        'max_attempts',
        'description',
        'randomize_questions',
    ];

    protected $casts = [
        'duration' => 'integer',
        'questions_count' => 'integer',
        'passing_score' => 'integer',
        'max_attempts' => 'integer',
        'randomize_questions' => 'boolean',
    ];

    /**
     * Get the institute that owns the exam type
     */
    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    /**
     * Get the subjects for this exam type
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'exam_type_subject');
    }

    /**
     * Get the exams of this type
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get the exam schedules for this type
     */
    public function examSchedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }

    /**
     * Get the exam results for this type
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Scope a query to only include active exam types
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes} minutes";
    }

    /**
     * Get the exam count
     */
    public function getExamCountAttribute(): int
    {
        return $this->exams()->count();
    }

    /**
     * Get the schedule count
     */
    public function getScheduleCountAttribute(): int
    {
        return $this->examSchedules()->count();
    }

    /**
     * Get the result count
     */
    public function getResultCountAttribute(): int
    {
        return $this->examResults()->count();
    }

    /**
     * Get the average score
     */
    public function getAverageScoreAttribute(): float
    {
        return $this->examResults()->avg('score') ?? 0;
    }

    /**
     * Get the pass rate
     */
    public function getPassRateAttribute(): float
    {
        $totalResults = $this->examResults()->count();
        if ($totalResults === 0) {
            return 0;
        }
        
        $passedResults = $this->examResults()
            ->where('score', '>=', $this->passing_score)
            ->count();
        
        return ($passedResults / $totalResults) * 100;
    }
} 