<?php

use App\Models\Chat\ChatConversationParticipant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.conversation.{conversationId}', function ($user, int $conversationId): bool {
    return ChatConversationParticipant::query()
        ->where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->whereNull('left_at')
        ->exists();
});
