<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatConversationParticipant extends Model
{
    protected $connection = 'ai_mysql';

    protected $table = 'chat_conversation_participants';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'last_read_message_id',
        'muted_at',
        'archived_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'muted_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }
}
