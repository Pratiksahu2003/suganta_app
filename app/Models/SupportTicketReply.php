<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicketReply extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'is_admin_reply',
        'attachment_path',
        'internal_notes'
    ];

    protected $casts = [
        'is_admin_reply' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['attachment'];

    /**
     * Relationships
     */
    public function supportTicket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get time since reply
     */
    public function getTimeSinceReply()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if reply has attachment
     */
    public function hasAttachment()
    {
        return !empty($this->attachment_path);
    }

    /**
     * Get attachment filename
     */
    public function getAttachmentFilename()
    {
        if (!$this->hasAttachment()) {
            return null;
        }
        return basename($this->attachment_path);
    }

    /**
     * Get attachment URL (uses storage_file_url for signed GCS URLs).
     */
    public function getAttachmentUrl()
    {
        if (!$this->hasAttachment()) {
            return null;
        }
        return storage_file_url($this->attachment_path);
    }

    /**
     * Get attachment object for API (path, url, name) - appended to JSON.
     */
    public function getAttachmentAttribute(): ?array
    {
        if (!$this->hasAttachment()) {
            return null;
        }
        return [
            'path' => $this->attachment_path,
            'url' => storage_file_url($this->attachment_path),
            'name' => $this->getAttachmentFilename(),
        ];
    }

    /**
     * Scope for admin replies
     */
    public function scopeAdminReplies($query)
    {
        return $query->where('is_admin_reply', true);
    }

    /**
     * Scope for user replies
     */
    public function scopeUserReplies($query)
    {
        return $query->where('is_admin_reply', false);
    }
} 