<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Area extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'areas';

    protected $fillable = [
        'name',
        'slug',
        'city_id',
        'description',
        'pincode',
        'latitude',
        'longitude',
        'is_active',
        'is_popular',
        'total_teachers',
        'total_institutes',
        'total_students',
        'nearby_areas',
        'landmarks',
        'transport_connectivity',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'total_teachers' => 'integer',
        'total_institutes' => 'integer',
        'total_students' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'nearby_areas' => 'array',
        'landmarks' => 'array',
        'transport_connectivity' => 'array',
    ];

    /**
     * Boot method to automatically generate slug
     */
    protected static function boot()
    {
        parent::boot();
        
        // Slug generation is now handled manually in the seeder
        // to ensure the format: {city_name}/home-tutors-near-by-{area_name}
    }

    /**
     * Generate unique slug for the area
     */
    public function generateSlug()
    {
        $slug = Str::slug($this->name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the city that owns the area
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the SEO content for this area
     */
    public function seoContent(): HasOne
    {
        return $this->hasOne(AreaSeoContent::class, 'area_id');
    }

    /**
     * Get teachers in this area
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(TeacherProfile::class, 'area', 'name');
    }

    /**
     * Get institutes in this area
     */
    public function institutes(): HasMany
    {
        return $this->hasMany(Institute::class, 'area', 'name');
    }

    /**
     * Get students in this area
     */
    public function students(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'area', 'name');
    }

    /**
     * Scope to get only active areas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only popular areas
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope to get areas by city
     */
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Get the area name attribute
     */
    public function getNameAttribute()
    {
        return $this->attributes['name'];
    }

    /**
     * Get the full area name with city
     */
    public function getFullNameAttribute()
    {
        return $this->name . ', ' . $this->city->name;
    }

    /**
     * Get the URL for this area
     */
    public function getUrlAttribute()
    {
        // The slug now contains the full path: {city_name}/home-tutors-near-by-{area_name}
        return url('/' . $this->slug);
    }
}
