<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $messageId,
        public int $userId,
        public string $readAt
    ) {
        Log::channel('reverb')->info('MessageRead dispatched', [
            'event' => 'chat.message.read',
            'channel' => 'private-chat.conversation.'.$conversationId,
            'message_id' => $messageId,
            'user_id' => $userId,
            'read_at' => $readAt,
        ]);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'read_at' => $this->readAt,
        ];
    }
}
