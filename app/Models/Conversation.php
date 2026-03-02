<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'initiator_id',
        'participant_id',
        'subject',
        'type',
        'status',
        'last_message_at',
        'metadata'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'metadata' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($conversation) {
            if (empty($conversation->conversation_id)) {
                $conversation->conversation_id = Str::uuid();
            }
        });
    }

    /**
     * Get the initiator of the conversation
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    /**
     * Get the participant of the conversation
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_id');
    }

    /**
     * Get all messages in the conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message in the conversation
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    /**
     * Scope to get conversations involving a specific user
     */
    public function scopeInvolving($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('initiator_id', $userId)
              ->orWhere('participant_id', $userId);
        });
    }

    /**
     * Scope to get active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the other participant in the conversation
     */
    public function getOtherParticipant($userId)
    {
        if ($this->initiator_id == $userId) {
            return $this->participant;
        }
        return $this->initiator;
    }

    /**
     * Check if a user is part of this conversation
     */
    public function involvesUser($userId): bool
    {
        return $this->initiator_id == $userId || $this->participant_id == $userId;
    }

    /**
     * Get unread message count for a user
     */
    public function getUnreadCount($userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('status', '!=', 'read')
            ->count();
    }

    /**
     * Mark messages as read for a user
     */
    public function markAsRead($userId): void
    {
        $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('status', '!=', 'read')
            ->update([
                'status' => 'read',
                'read_at' => now()
            ]);
    }
} 