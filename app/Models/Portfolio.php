<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'images',
        'files',
        'category',
        'tags',
        'url',
        'status',
        'order',
        'is_featured',
    ];

    protected $casts = [
        'images' => 'array',
        'files' => 'array',
        'is_featured' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the user that owns the portfolio
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter published portfolios
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to filter featured portfolios
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
