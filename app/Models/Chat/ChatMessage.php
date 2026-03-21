<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    protected $connection = 'ai_mysql';

    protected $table = 'chat_messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'reply_to',
        'meta',
        'is_edited',
        'edited_at',
        'deleted_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ChatMessageRead::class, 'message_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ChatMessageReaction::class, 'message_id');
    }
}
