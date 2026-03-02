<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstituteExam extends Model
{
    use HasFactory;

    protected $table = 'institute_exams';

    protected $fillable = [
        'user_id',
        'exam_id',
        'course_name',
        'image',
        'course_duration_months',
        'students_cleared',
        'fee_range_min',
        'fee_range_max',
        'course_description',
        'course_features',
        'study_materials',
        'faculty_details',
        'teaching_mode',
        'schedule_details',
        'facilities',
        'admission_requirements',
        'course_start_date',
        'course_end_date',
        'admission_deadline',
        'scholarship_available',
        'scholarship_details',
        'placement_assistance',
        'achievements',
        'status',
        'success_rate',
        'batch_size',
        'courses_offered'
    ];

    protected $casts = [
        'course_features' => 'array',
        'study_materials' => 'array',
        'faculty_details' => 'array',
        'schedule_details' => 'array',
        'facilities' => 'array',
        'achievements' => 'array',
        'courses_offered' => 'array',
        'scholarship_available' => 'boolean',
        'placement_assistance' => 'boolean',
        'course_start_date' => 'date',
        'course_end_date' => 'date',
        'admission_deadline' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
