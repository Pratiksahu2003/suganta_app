<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReactionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $messageId,
        public int $userId,
        public ?string $reaction
    ) {
        Log::channel('reverb')->info('ReactionUpdated dispatched', [
            'event' => 'chat.message.reaction.updated',
            'channel' => 'private-chat.conversation.'.$conversationId,
            'message_id' => $messageId,
            'user_id' => $userId,
            'reaction' => $reaction,
        ]);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.reaction.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'reaction' => $this->reaction,
        ];
    }
}
