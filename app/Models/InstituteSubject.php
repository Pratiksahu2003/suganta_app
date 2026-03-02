<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstituteSubject extends Model
{
    use HasFactory;

    protected $table = 'institute_subjects';

    protected $fillable = [
        'user_id',
        'subject_id',
        'course_duration',
        'fees',
        'course_description',
        'teaching_mode',
        'batch_size',
        'timings',
        'status',
        'grade_levels'
    ];

    protected $casts = [
        'grade_levels' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
