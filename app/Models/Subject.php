<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'short_name',
        'description',
        'category',
        'level',
        'grade_level',
        'icon',
        'color',
        'sort_order',
        'status',
        'is_active',
        'is_popular',
        'image',
        'exam_boards',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'exam_boards' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subject) {
            if (empty($subject->slug)) {
                $subject->slug = Str::slug($subject->name);
            }
        });

        static::updating(function ($subject) {
            if ($subject->isDirty('name') && empty($subject->slug)) {
                $subject->slug = Str::slug($subject->name);
            }
        });
    }

    /**
     * Get exams that include this subject
     */
    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_subjects')
                   ->withPivot(['weightage', 'marks', 'is_optional'])
                   ->withTimestamps();
    }

    /**
     * Get teachers who teach this subject
     */
    public function teachers()
    {
        return $this->belongsToMany(TeacherProfile::class, 'teacher_subjects', 'subject_id', 'teacher_id')
                   ->withPivot(['subject_rate', 'proficiency_level'])
                   ->withTimestamps();
    }

    /**
     * Get teacher profiles for this subject (direct relationship)
     */
    public function teacherProfiles()
    {
        return $this->hasMany(TeacherProfile::class, 'subject_id');
    }

    /**
     * Get institutes that offer this subject
     */
    public function institutes()
    {
        return $this->belongsToMany(Institute::class, 'institute_subjects')
                   ->withPivot(['course_duration', 'fees', 'batch_size'])
                   ->withTimestamps();
    }

    /**
     * Get branches that offer this subject
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'school_branch_subjects', 'subject_id', 'branch_id')
                   ->withPivot(['grade_levels'])
                   ->withTimestamps();
    }

    /**
     * Get students who are learning this subject (through teachers)
     */
    public function students()
    {
        return $this->belongsToMany(StudentProfile::class, 'student_teachers', 'subject_id', 'student_id')
            ->withPivot(['teacher_id', 'start_date', 'end_date', 'status', 'rating', 'review'])
            ->withTimestamps();
    }

    /**
     * Scope for active subjects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for subjects by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for subjects by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get subject URL
     */
    public function getUrlAttribute()
    {
        return route('subjects.show', $this->slug);
    }

    /**
     * Get category display name
     */
    public function getCategoryDisplayAttribute()
    {
        return match($this->category) {
            'science' => 'Science',
            'mathematics' => 'Mathematics',
            'language' => 'Language',
            'social_science' => 'Social Science',
            'computer_science' => 'Computer Science',
            'engineering' => 'Engineering',
            'medical' => 'Medical',
            'commerce' => 'Commerce',
            'arts' => 'Arts',
            'law' => 'Law',
            'management' => 'Management',
            'general_knowledge' => 'General Knowledge',
            'current_affairs' => 'Current Affairs',
            'reasoning' => 'Reasoning',
            'quantitative_aptitude' => 'Quantitative Aptitude',
            default => 'Other'
        };
    }

    /**
     * Get level display name
     */
    public function getLevelDisplayAttribute()
    {
        return match($this->level) {
            'primary' => 'Primary (1-5)',
            'secondary' => 'Secondary (6-10)',
            'higher_secondary' => 'Higher Secondary (11-12)',
            'undergraduate' => 'Undergraduate',
            'postgraduate' => 'Postgraduate',
            'competitive' => 'Competitive Exams',
            'professional' => 'Professional',
            default => 'All Levels'
        };
    }

    /**
     * Get total teachers count for this subject
     */
    public function getTotalTeachersAttribute()
    {
        return $this->teachers()->count();
    }

    /**
     * Get total institutes count for this subject
     */
    public function getTotalInstitutesAttribute()
    {
        return $this->institutes()->count();
    }

    /**
     * Get total exams count for this subject
     */
    public function getTotalExamsAttribute()
    {
        return $this->exams()->count();
    }

    /**
     * Get related subjects
     */
    public function getRelatedSubjects($limit = 5)
    {
        return static::where('id', '!=', $this->id)
                    ->where('category', $this->category)
                    ->orWhere('level', $this->level)
                    ->active()
                    ->orderBy('sort_order')
                    ->take($limit)
                    ->get();
    }

    /**
     * Get top teachers for this subject
     */
    public function getTopTeachers($limit = 5)
    {
        return $this->teachers()
                   ->where('is_active', true)
                   ->orderBy('teacher_subjects.proficiency_level', 'desc')
                   ->orderBy('teacher_subjects.experience_years', 'desc')
                   ->take($limit)
                   ->get();
    }

    /**
     * Get top institutes for this subject
     */
    public function getTopInstitutes($limit = 5)
    {
        return $this->institutes()
                   ->where('is_active', true)
                   ->orderBy('rating', 'desc')
                   ->take($limit)
                   ->get();
    }

    /**
     * Get competitive exams for this subject
     */
    public function getCompetitiveExams($limit = 10)
    {
        return $this->exams()
                   ->where('is_active', true)
                   ->whereIn('exam_type', ['national', 'state', 'government'])
                   ->orderBy('featured', 'desc')
                   ->orderBy('exam_date', 'asc')
                   ->take($limit)
                   ->get();
    }

    /**
     * Check if subject is popular
     */
    public function getIsPopularAttribute()
    {
        $teacherCount = $this->total_teachers;
        $examCount = $this->total_exams;
        
        return $teacherCount >= 50 || $examCount >= 5;
    }

    /**
     * Get subject statistics
     */
    public function getStatsAttribute()
    {
        return [
            'total_teachers' => $this->total_teachers,
            'total_institutes' => $this->total_institutes,
            'total_exams' => $this->total_exams,
            'avg_teacher_experience' => $this->teachers()->avg('teacher_subjects.experience_years') ?: 0,
        ];
    }
}
