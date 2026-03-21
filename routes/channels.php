<?php

use App\Models\Chat\ChatConversationParticipant;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('chat.conversation.{conversationId}', function ($user, int $conversationId): bool {
    $authorized = ChatConversationParticipant::query()
        ->where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->whereNull('left_at')
        ->exists();

    Log::channel('reverb')->info('Channel auth attempt', [
        'channel' => 'private-chat.conversation.'.$conversationId,
        'user_id' => $user->id,
        'authorized' => $authorized,
    ]);

    return $authorized;
});
