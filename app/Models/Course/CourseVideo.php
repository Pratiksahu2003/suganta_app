<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'heading',
        'description',
        'thumbnail',
        'youtube_video_id',
        'youtube_video_url',
        'duration_minutes',
        'order',
        'is_preview',
        'resources',
        'transcript',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'order' => 'integer',
        'is_preview' => 'boolean',
        'resources' => 'array',
    ];

    /**
     * Get the course that owns this video
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get YouTube embed URL
     */
    public function getEmbedUrlAttribute()
    {
        if ($this->youtube_video_id) {
            return "https://www.youtube.com/embed/{$this->youtube_video_id}";
        }
        return null;
    }

    /**
     * Get thumbnail URL (custom thumbnail or YouTube thumbnail)
     */
    public function getThumbnailUrlAttribute()
    {
        // Use custom thumbnail if available
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }
        // Fallback to YouTube thumbnail
        if ($this->youtube_video_id) {
            return "https://img.youtube.com/vi/{$this->youtube_video_id}/maxresdefault.jpg";
        }
        return null;
    }

    /**
     * Get YouTube watch URL
     */
    public function getWatchUrlAttribute()
    {
        if ($this->youtube_video_id) {
            return "https://www.youtube.com/watch?v={$this->youtube_video_id}";
        }
        return null;
    }
}

