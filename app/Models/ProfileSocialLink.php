<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileSocialLink extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'profile_social_links';

    protected $fillable = [
        'profile_id',
        'platform',
        'url',
        'username',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the profile that owns the social link
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the platform icon class
     */
    public function getPlatformIconAttribute()
    {
        $icons = [
            'facebook' => 'bi-facebook text-primary',
            'twitter' => 'bi-twitter text-info',
            'instagram' => 'bi-instagram text-danger',
            'linkedin' => 'bi-linkedin text-primary',
            'youtube' => 'bi-youtube text-danger',
            'tiktok' => 'bi-tiktok text-dark',
            'telegram' => 'bi-telegram text-primary',
            'discord' => 'bi-discord text-primary',
            'github' => 'bi-github text-dark',
            'whatsapp' => 'bi-whatsapp text-success',
        ];

        return $icons[$this->platform] ?? 'bi-link text-secondary';
    }

    /**
     * Get the platform display name
     */
    public function getPlatformNameAttribute()
    {
        return ucfirst($this->platform);
    }
} 