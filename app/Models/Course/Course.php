<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Subject;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'short_description',
        'subject_id',
        'thumbnail',
        'preview_video_id',
        'price',
        'original_price',
        'level',
        'language',
        'what_you_will_learn',
        'requirements',
        'target_audience',
        'total_duration_minutes',
        'total_videos',
        'total_students',
        'rating',
        'total_reviews',
        'status',
        'is_featured',
        'is_free',
        'published_at',
        'meta_keywords',
        'meta_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'what_you_will_learn' => 'array',
        'requirements' => 'array',
        'target_audience' => 'array',
        'meta_keywords' => 'array',
        'is_featured' => 'boolean',
        'is_free' => 'boolean',
        'published_at' => 'datetime',
        'total_duration_minutes' => 'integer',
        'total_videos' => 'integer',
        'total_students' => 'integer',
        'total_reviews' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
                // Ensure unique slug
                $originalSlug = $course->slug;
                $count = 1;
                while (static::where('slug', $course->slug)->exists()) {
                    $course->slug = $originalSlug . '-' . $count++;
                }
            }
        });

        static::updating(function ($course) {
            if ($course->isDirty('title') && empty($course->slug)) {
                $course->slug = Str::slug($course->title);
                // Ensure unique slug
                $originalSlug = $course->slug;
                $count = 1;
                while (static::where('slug', $course->slug)->where('id', '!=', $course->id)->exists()) {
                    $course->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    /**
     * Get the user who created the course
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject for the course
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get all videos for the course
     */
    public function videos()
    {
        return $this->hasMany(CourseVideo::class)->orderBy('order');
    }

    /**
     * Get all sections for the course
     */
    public function sections()
    {
        return $this->hasMany(CourseSection::class)->orderBy('order');
    }

    /**
     * Get all enrollments for the course
     */
    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    /**
     * Check if course is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Get preview video URL
     */
    public function getPreviewVideoUrlAttribute()
    {
        if ($this->preview_video_id) {
            return "https://www.youtube.com/embed/{$this->preview_video_id}";
        }
        return null;
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }
        if ($this->preview_video_id) {
            return "https://img.youtube.com/vi/{$this->preview_video_id}/maxresdefault.jpg";
        }
        return null;
    }

    /**
     * Scope for published courses
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for featured courses
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}

