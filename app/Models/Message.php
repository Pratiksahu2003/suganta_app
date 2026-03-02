<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'status',
        'read_at',
        'metadata'
    ];
    
    protected $dates = [
        'read_at',
        'deleted_at',
        'edited_at'
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Get the conversation this message belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender of the message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Scope to get unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('status', '!=', 'read');
    }

    /**
     * Scope to get messages by sender
     */
    public function scopeBySender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }


    /**
     * Check if message is read
     */
    public function isRead(): bool
    {
        return $this->status === 'read';
    }

    /**
     * Check if message is sent by a specific user
     */
    public function isSentBy($userId): bool
    {
        return $this->sender_id === $userId;
    }

    /**
     * Get formatted time
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at->format('H:i');
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y');
    }

    /**
     * Get reactions for this message
     */
    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Get reaction counts for this message
     */
    public function getReactionCountsAttribute(): array
    {
        return $this->reactions()
                   ->selectRaw('reaction, COUNT(*) as count')
                   ->groupBy('reaction')
                   ->pluck('count', 'reaction')
                   ->toArray();
    }

    /**
     * Check if a user has reacted to this message
     */
    public function hasReactionFrom($userId): bool
    {
        return $this->reactions()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's reaction to this message
     */
    public function getUserReaction($userId): ?string
    {
        $reaction = $this->reactions()->where('user_id', $userId)->first();
        return $reaction ? $reaction->reaction : null;
    }

    /**
     * Mark message as read
     */
    public function markAsRead($userId): void
    {
        if ($this->sender_id !== $userId) {
            $this->update([
                'status' => 'read',
                'read_at' => now()
            ]);
        }
    }


    /**
     * Get message type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'image' => 'fas fa-image',
            'file' => 'fas fa-file',
            'audio' => 'fas fa-microphone',
            'video' => 'fas fa-video',
            default => 'fas fa-comment'
        };
    }
} 