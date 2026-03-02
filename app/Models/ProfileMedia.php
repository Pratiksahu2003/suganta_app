<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'type',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'description',
        'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the profile that owns the media
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the media file URL
     */
    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }

    /**
     * Get the file size in human readable format
     */
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the type display name
     */
    public function getTypeNameAttribute()
    {
        $types = [
            'gallery' => 'Gallery Image',
            'document' => 'Document',
            'certificate' => 'Certificate',
        ];

        return $types[$this->type] ?? $this->type;
    }
} 