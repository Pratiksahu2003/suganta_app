<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'max_images',
        'max_files',
        'features',
        'is_popular',
        'is_active',
        'sort_order',
        's_type',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_images' => 'integer',
        'max_files' => 'integer',
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        's_type' => 'integer',
    ];

    /**
     * Get subscriptions for this plan
     */
    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get active subscriptions for this plan
     */
    public function activeSubscriptions()
    {
        return $this->hasMany(UserSubscription::class)->where('status', 'active');
    }

    /**
     * Scope to filter active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter popular plans
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

 

    public static function scopeSpecificPlan( $s_type =1)
    {
        return self::where('s_type', $s_type )
        ->orderBy('sort_order', 'asc')
        ->orderBy('price', 'asc')
        ->get();
    }
}
