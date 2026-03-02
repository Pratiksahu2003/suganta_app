<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Profile;
class TeacherSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'title',
        'description',
        'price',
        'max_students',
        'status',
        'date',
        'time',
        'duration',
        'settings',
        'is_active',
        'type',
        'location',
        'created_by',
        'exam_id',
        'exam_category_id',
        'institute_id',
        'notes',
        'demo_video',
        'meeting_link',
        'additional_info',
        'google_meet_event_id'
    ];

    protected $casts = [
        'settings' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'time' => 'datetime:H:i',
    ];

    /**
     * Get the teacher that owns the session
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'created_by', 'user_id');
    }

    /**
     * Alias for teacher() method for backward compatibility
     */
    public function teacherProfile()
    {
        return $this->teacher();
    }


    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function examCategory()
    {
        return $this->belongsTo(ExamCategory::class);
    }

    /**
     * Get the subject for the session
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get bookings for this session
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'teacher_id', 'teacher_profile_id')
            ->where('subject_id', $this->subject_id);
    }

    /**
     * Get students enrolled in this session
     */
    public function students()
    {
        return $this->belongsToMany(StudentProfile::class, 'session_enrollments', 'session_id', 'student_id')
            ->withPivot(['enrolled_at', 'status', 'payment_status', 'rating', 'review'])
            ->withTimestamps();
    }

    /**
     * Scope to get active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get sessions by teacher
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_profile_id', $teacherId);
    }

    /**
     * Scope to get sessions by subject
     */
    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope to get sessions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('session_type', $type);
    }

    /**
     * Scope to get sessions by grade level
     */
    public function scopeByGradeLevel($query, $gradeLevel)
    {
        return $query->where('grade_level', $gradeLevel);
    }

    /**
     * Scope to get sessions within price range
     */
    public function scopeWithinPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }
}
