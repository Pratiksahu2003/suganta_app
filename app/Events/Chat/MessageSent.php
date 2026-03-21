<?php

namespace App\Events\Chat;

use App\Models\Chat\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversation.'.$this->message->conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message,
            'reply_to' => $this->message->reply_to,
            'meta' => $this->message->meta,
            'is_edited' => $this->message->is_edited,
            'created_at' => optional($this->message->created_at)->toIso8601String(),
        ];
    }
}
