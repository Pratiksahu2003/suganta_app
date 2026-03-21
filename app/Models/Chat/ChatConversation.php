<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    protected $connection = 'ai_mysql';

    protected $table = 'chat_conversations';

    protected $fillable = [
        'type',
        'title',
        'created_by',
        'last_message_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(ChatConversationParticipant::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }
}
