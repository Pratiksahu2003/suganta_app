<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationReadStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $userId,
        public ?int $lastReadMessageId,
        public string $readAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'chat.conversation.read_state';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'last_read_message_id' => $this->lastReadMessageId,
            'read_at' => $this->readAt,
        ];
    }
}
