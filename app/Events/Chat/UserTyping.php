<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $userId,
        public bool $isTyping
    ) {
        Log::channel('reverb')->info('UserTyping dispatched', [
            'event' => 'chat.user.typing',
            'channel' => 'private-chat.conversation.'.$conversationId,
            'user_id' => $userId,
            'is_typing' => $isTyping,
        ]);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'chat.user.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'is_typing' => $this->isTyping,
        ];
    }
}
