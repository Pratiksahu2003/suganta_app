<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExamCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'sort_order',
        'status',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get exams in this category
     */
    public function exams()
    {
        return $this->hasMany(Exam::class)->orderBy('sort_order');
    }

    /**
     * Get active exams in this category
     */
    public function activeExams()
    {
        return $this->hasMany(Exam::class)->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        // Since exam_categories table doesn't have a status column, 
        // we'll return all categories as active
        return $query;
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get category URL
     */
    public function getUrlAttribute()
    {
        return route('exam-categories.show', $this->slug);
    }

    /**
     * Get exam count in this category
     */
    public function getExamCountAttribute()
    {
        return $this->exams()->count();
    }

    /**
     * Get active exam count in this category
     */
    public function getActiveExamCountAttribute()
    {
        return $this->activeExams()->count();
    }

    /**
     * Get total teachers in this category
     */
    public function getTotalTeachersAttribute()
    {
        return Teacher::whereHas('exams.category', function ($query) {
            $query->where('id', $this->id);
        })->count();
    }

    /**
     * Get total institutes in this category
     */
    public function getTotalInstitutesAttribute()
    {
        return Institute::whereHas('exams.category', function ($query) {
            $query->where('id', $this->id);
        })->count();
    }

    /**
     * Get featured exams in this category
     */
    public function getFeaturedExams($limit = 5)
    {
        return $this->activeExams()
                   ->where('featured', true)
                   ->orderBy('sort_order')
                   ->take($limit)
                   ->get();
    }

    /**
     * Get upcoming exams in this category
     */
    public function getUpcomingExams($limit = 10)
    {
        return $this->activeExams()
                   ->where('exam_date', '>=', now())
                   ->orderBy('exam_date', 'asc')
                   ->take($limit)
                   ->get();
    }
} 