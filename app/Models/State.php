<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status',
        'country_id'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the cities for this state
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Scope to get only active states
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
