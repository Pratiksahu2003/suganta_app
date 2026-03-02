<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WebStory extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'custom_url',
        'images',
        'is_active',
        'order',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope to filter active web stories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('created_at', 'desc');
    }

    /**
     * Get image URLs
     */
    public function getImageUrlsAttribute()
    {
        if (!$this->images) {
            return [];
        }

        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $this->images);
    }

    /**
     * Generate a unique slug for the web story
     */
    public function generateUniqueSlug($title = null)
    {
        $title = $title ?? $this->title;
        $baseSlug = \Illuminate\Support\Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            
            // Prevent infinite loops
            if ($counter > 1000) {
                $slug = $baseSlug . '-' . time();
                break;
            }
        }
        
        return $slug;
    }
    
    /**
     * Check if a slug already exists
     */
    private function slugExists($slug)
    {
        $query = static::where('slug', $slug);
        
        // If this is an existing story, exclude it from the check
        if ($this->exists && $this->id) {
            $query->where('id', '!=', $this->id);
        }
        
        return $query->exists();
    }
    
    /**
     * Check if slug is unique (useful for validation)
     */
    public static function isSlugUnique($slug, $excludeId = null)
    {
        $query = static::where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return !$query->exists();
    }
}
