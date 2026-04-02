<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'price',
        'category',
        'type',
        'file_path',
        'thumbnail',
        'images',
        'status',
        'views_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'views_count' => 'integer',
        'images' => 'array',
    ];

    /**
     * Get the user who owns the listing
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active listings
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for specific type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
