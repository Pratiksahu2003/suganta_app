<?php

namespace App\Http\Controllers\Api\V3\Chat;

use App\Events\Chat\MessageRead;
use App\Events\Chat\MessageSent;
use App\Events\Chat\ReactionUpdated;
use App\Events\Chat\UserTyping;
use App\Http\Controllers\Controller;
use App\Models\Chat\ChatConversation;
use App\Models\Chat\ChatConversationParticipant;
use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatMessageReaction;
use App\Models\Chat\ChatMessageRead;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    use ApiResponse;

    public function index(Request $request, int $conversation): JsonResponse
    {
        $auth = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isParticipant($chat->id, $auth->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        $messages = ChatMessage::query()
            ->where('conversation_id', $chat->id)
            ->whereNull('deleted_at')
            ->with(['reactions', 'reads'])
            ->orderByDesc('id')
            ->paginate(50);

        return $this->success('Messages fetched successfully.', $messages);
    }

    public function store(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'reply_to' => ['nullable', 'integer', 'exists:ai_mysql.chat_messages,id'],
            'meta' => ['nullable', 'array'],
        ]);

        $auth = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isParticipant($chat->id, $auth->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        if (! empty($validated['reply_to'])) {
            $replyToMessage = ChatMessage::query()
                ->where('id', $validated['reply_to'])
                ->where('conversation_id', $chat->id)
                ->whereNull('deleted_at')
                ->first();

            if (! $replyToMessage) {
                return $this->error('Reply target message is invalid for this conversation.', 422);
            }
        }

        $message = DB::connection('ai_mysql')->transaction(function () use ($validated, $chat, $auth) {
            $message = ChatMessage::create([
                'conversation_id' => $chat->id,
                'sender_id' => $auth->id,
                'message' => $validated['message'],
                'reply_to' => $validated['reply_to'] ?? null,
                'meta' => $validated['meta'] ?? null,
            ]);

            $chat->update([
                'last_message_id' => $message->id,
                'last_message_at' => now(),
            ]);

            return $message;
        });

        broadcast(new MessageSent($message))->toOthers();

        return $this->created([
            'message' => $message->load(['reactions', 'reads']),
        ], 'Message sent successfully.');
    }

    public function update(Request $request, int $message): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $auth = $request->user();
        $chatMessage = ChatMessage::findOrFail($message);

        if ((int) $chatMessage->sender_id !== (int) $auth->id) {
            return $this->forbidden('You can edit only your own messages.');
        }

        if ($chatMessage->deleted_at !== null) {
            return $this->error('Deleted messages cannot be edited.', 422);
        }

        $chatMessage->update([
            'message' => $validated['message'],
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        broadcast(new MessageSent($chatMessage->fresh()))->toOthers();

        return $this->success('Message updated successfully.', ['message' => $chatMessage->fresh()]);
    }

    public function destroy(Request $request, int $message): JsonResponse
    {
        $auth = $request->user();
        $chatMessage = ChatMessage::findOrFail($message);

        if ((int) $chatMessage->sender_id !== (int) $auth->id) {
            return $this->forbidden('You can delete only your own messages.');
        }

        if ($chatMessage->deleted_at !== null) {
            return $this->success('Message already deleted.');
        }

        $chatMessage->update(['deleted_at' => now()]);

        return $this->success('Message deleted successfully.');
    }

    public function markRead(Request $request, int $message): JsonResponse
    {
        $auth = $request->user();
        $chatMessage = ChatMessage::findOrFail($message);

        if (! $this->isParticipant($chatMessage->conversation_id, $auth->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        if ($chatMessage->deleted_at !== null) {
            return $this->error('Deleted messages cannot be marked as read.', 422);
        }

        $read = ChatMessageRead::updateOrCreate(
            ['message_id' => $chatMessage->id, 'user_id' => $auth->id],
            ['read_at' => now()]
        );

        broadcast(new MessageRead(
            $chatMessage->conversation_id,
            $chatMessage->id,
            $auth->id,
            $read->read_at->toIso8601String()
        ))->toOthers();

        return $this->success('Message marked as read.');
    }

    public function react(Request $request, int $message): JsonResponse
    {
        $validated = $request->validate([
            'reaction' => ['required', 'string', 'max:32'],
        ]);

        $auth = $request->user();
        $chatMessage = ChatMessage::findOrFail($message);

        if (! $this->isParticipant($chatMessage->conversation_id, $auth->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        if ($chatMessage->deleted_at !== null) {
            return $this->error('Deleted messages cannot be reacted to.', 422);
        }

        ChatMessageReaction::updateOrCreate(
            ['message_id' => $chatMessage->id, 'user_id' => $auth->id],
            ['reaction' => $validated['reaction']]
        );

        broadcast(new ReactionUpdated(
            $chatMessage->conversation_id,
            $chatMessage->id,
            $auth->id,
            $validated['reaction']
        ))->toOthers();

        return $this->success('Reaction updated successfully.');
    }

    public function removeReaction(Request $request, int $message): JsonResponse
    {
        $auth = $request->user();
        $chatMessage = ChatMessage::findOrFail($message);

        if (! $this->isParticipant($chatMessage->conversation_id, $auth->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        if ($chatMessage->deleted_at !== null) {
            return $this->error('Deleted messages cannot be reacted to.', 422);
        }

        ChatMessageReaction::where('message_id', $chatMessage->id)
            ->where('user_id', $auth->id)
            ->delete();

        broadcast(new ReactionUpdated(
            $chatMessage->conversation_id,
            $chatMessage->id,
            $auth->id,
            null
        ))->toOthers();

        return $this->success('Reaction removed successfully.');
    }

    public function typing(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'is_typing' => ['required', 'boolean'],
        ]);

        $auth = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isParticipant($chat->id, $auth->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        broadcast(new UserTyping($chat->id, $auth->id, (bool) $validated['is_typing']))->toOthers();

        return $this->success('Typing status broadcasted.');
    }

    protected function isParticipant(int $conversationId, int $userId): bool
    {
        return ChatConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
    }
}
