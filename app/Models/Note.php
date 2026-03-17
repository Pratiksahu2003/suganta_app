<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // Keep for backward compatibility
        'note_type_id',
        'file_path',
        'category', // Keep for backward compatibility
        'note_category_id',
        'description',
        'uploaded_by',
        'download_count',
        'is_active',
        'price',
        'is_paid',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_paid' => 'boolean',
        'download_count' => 'integer',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who uploaded the note
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the note type
     */
    public function noteType()
    {
        return $this->belongsTo(NoteType::class);
    }

    /**
     * Get the note category
     */
    public function noteCategory()
    {
        return $this->belongsTo(NoteCategory::class);
    }

    /**
     * Get the file URL (uses storage_file_url for configurable disk / signed URLs).
     */
    public function getFileUrlAttribute()
    {
        return $this->file_path ? storage_file_url($this->file_path) : null;
    }

    /**
     * Get the file size
     */
    public function getFileSizeAttribute()
    {
        $disk = config('filesystems.upload_disk', 'public');
        if ($this->file_path && Storage::disk($disk)->exists($this->file_path)) {
            $bytes = Storage::disk($disk)->size($this->file_path);
            $units = ['B', 'KB', 'MB', 'GB'];
            for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                $bytes /= 1024;
            }
            return round($bytes, 2) . ' ' . $units[$i];
        }
        return 'N/A';
    }

    /**
     * Get purchases for this note
     */
    public function purchases()
    {
        return $this->hasMany(NotePurchase::class);
    }

    /**
     * Check if user has purchased this note
     */
    public function isPurchasedBy($userId)
    {
        if (!$userId) return false;
        return $this->purchases()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Get user's purchase for this note
     */
    public function getUserPurchase($userId)
    {
        if (!$userId) return null;
        return $this->purchases()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();
    }
}
