<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_name',
        'state_id',
        'nearby',
        'popular_cities',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'nearby' => 'boolean',
        'popular_cities' => 'boolean',
    ];

    /**
     * Get the state that owns the city
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the SEO content for this city
     */
    public function seoContent(): HasOne
    {
        return $this->hasOne(CitySeoContent::class);
    }

    /**
     * Get the areas in this city
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    /**
     * Scope to get only active cities
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Get the city name attribute
     */
    public function getNameAttribute()
    {
        return $this->city_name;
    }
}
