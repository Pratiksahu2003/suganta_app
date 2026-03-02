<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the course that owns this section
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get videos in this section
     */
    public function videos()
    {
        return $this->hasMany(CourseVideo::class)->orderBy('order');
    }
}

