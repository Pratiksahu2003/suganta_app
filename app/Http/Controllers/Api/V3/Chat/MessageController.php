<?php

namespace App\Http\Controllers\Api\V3\Chat;

use App\Events\Chat\MessageRead;
use App\Events\Chat\MessageSent;
use App\Events\Chat\ReactionUpdated;
use App\Events\Chat\UserTyping;
use App\Http\Controllers\Api\V3\Chat\Concerns\InteractsWithChatCache;
use App\Http\Controllers\Controller;
use App\Models\Chat\ChatConversation;
use App\Models\Chat\ChatConversationParticipant;
use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatMessageReaction;
use App\Models\Chat\ChatMessageRead;
use App\Models\User;
use App\Services\FirebasePushService;
use App\Support\ChatPlainText;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    use ApiResponse, InteractsWithChatCache;

    public function __construct(private readonly FirebasePushService $firebasePushService)
    {
    }

    public function index(Request $request, int $conversation): JsonResponse
    {
        $request->validate([
            'before_id' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $auth = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isParticipant($chat->id, $auth->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        $perPage = (int) $request->query('per_page', 50);
        $beforeId = $request->filled('before_id') ? (int) $request->query('before_id') : null;
        $page = max(1, (int) $request->query('page', 1));
        $conversationVersion = $this->chatConversationVersion($chat->id);
        $userVersion = $this->chatUserVersion($auth->id);
        $cacheKey = 'chat:v3:messages:index:'.$chat->id.':'.$auth->id.':'.$conversationVersion.':'.$userVersion.':'.sha1(json_encode([
            'before_id' => $beforeId,
            'per_page' => $perPage,
            'page' => $page,
        ], JSON_UNESCAPED_UNICODE));

        $payload = \Illuminate\Support\Facades\Cache::remember($cacheKey, $this->chatCacheTtlSeconds(), function () use ($chat, $request, $perPage, $auth) {
            $messages = ChatMessage::query()
                ->where('conversation_id', $chat->id)
                ->whereNull('deleted_at')
                ->when($request->filled('before_id'), function ($q) use ($request): void {
                    $q->where('id', '<', (int) $request->query('before_id'));
                })
                ->with([
                    'reactions',
                    'reads',
                    'replyTo' => static function ($query): void {
                        $query->select(['id', 'conversation_id', 'sender_id', 'message', 'created_at', 'deleted_at']);
                    },
                ])
                ->orderByDesc('id')
                ->paginate($perPage);

            $viewerId = $auth->id;
            $messages->through(fn (ChatMessage $m) => $this->serializeChatMessage($m, $viewerId));

            return $messages->toArray();
        });

        return $this->success('Messages fetched successfully.', $payload);
    }

    public function store(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'reply_to' => ['nullable', 'integer', 'exists:ai_mysql.chat_messages,id'],
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

        $body = ChatPlainText::sanitize($validated['message']);
        if (ChatPlainText::isEmpty($body)) {
            return $this->validationError(['message' => ['Message must contain visible text or emoji.']]);
        }

        $message = DB::connection('ai_mysql')->transaction(function () use ($validated, $chat, $auth, $body) {
            $message = ChatMessage::create([
                'conversation_id' => $chat->id,
                'sender_id' => $auth->id,
                'message' => $body,
                'reply_to' => $validated['reply_to'] ?? null,
                'meta' => null,
            ]);

            $chat->update([
                'last_message_id' => $message->id,
                'last_message_at' => now(),
            ]);

            return $message;
        });
        $this->flushConversationReadCaches($chat->id, [$auth->id]);

        broadcast(new MessageSent($message))->toOthers();
        $this->sendPushForNewMessage($chat->id, $message, $auth->id, $auth->name);

        return $this->created([
            'message' => $this->serializeChatMessage($message->load([
                'reactions',
                'reads',
                'replyTo' => static function ($query): void {
                    $query->select(['id', 'conversation_id', 'sender_id', 'message', 'created_at', 'deleted_at']);
                },
            ]), $auth->id),
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

        $body = ChatPlainText::sanitize($validated['message']);
        if (ChatPlainText::isEmpty($body)) {
            return $this->validationError(['message' => ['Message must contain visible text or emoji.']]);
        }

        $chatMessage->update([
            'message' => $body,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
        $this->flushConversationReadCaches($chatMessage->conversation_id, [$auth->id]);

        $fresh = $chatMessage->fresh()->load([
            'reactions',
            'reads',
            'replyTo' => static function ($query): void {
                $query->select(['id', 'conversation_id', 'sender_id', 'message', 'created_at', 'deleted_at']);
            },
        ]);

        broadcast(new MessageSent($fresh))->toOthers();

        return $this->success('Message updated successfully.', [
            'message' => $this->serializeChatMessage($fresh, $auth->id),
        ]);
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
        $this->flushConversationReadCaches($chatMessage->conversation_id, [$auth->id]);

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

        $participant = ChatConversationParticipant::query()
            ->where('conversation_id', $chatMessage->conversation_id)
            ->where('user_id', $auth->id)
            ->whereNull('left_at')
            ->first();
        if ($participant !== null) {
            $current = $participant->last_read_message_id;
            if ($current === null || $chatMessage->id > $current) {
                $participant->last_read_message_id = $chatMessage->id;
                $participant->save();
            }
        }
        $this->flushConversationReadCaches($chatMessage->conversation_id, [$auth->id]);

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
            'reaction' => [
                'required',
                'string',
                'max:16',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || preg_match('/\p{Extended_Pictographic}/u', $value) !== 1) {
                        $fail('Reaction must be emoji-based (text-only stickers are not supported).');
                    }
                },
            ],
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
        $this->flushConversationReadCaches($chatMessage->conversation_id, [$auth->id]);

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
        $this->flushConversationReadCaches($chatMessage->conversation_id, [$auth->id]);

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

    /**
     * @return array<string, mixed>
     */
    protected function serializeChatMessage(ChatMessage $message, ?int $viewerUserId = null): array
    {
        $replyPayload = $this->replyToMessagePayload($message);
        $message->unsetRelation('replyTo');

        $reactions = $message->relationLoaded('reactions') ? $message->reactions : collect();
        $reads = $message->relationLoaded('reads') ? $message->reads : collect();

        $reactionSummary = $reactions->groupBy('reaction')->map(function ($group) {
            $first = $group->first();

            return [
                'emoji' => $first !== null ? $first->reaction : '',
                'count' => $group->count(),
                'user_ids' => $group->pluck('user_id')->unique()->values()->all(),
            ];
        })->values()->all();

        $myReaction = null;
        if ($viewerUserId !== null) {
            $myReaction = $reactions->firstWhere('user_id', $viewerUserId)?->reaction;
        }

        $data = $message->toArray();
        unset($data['reactions'], $data['reads']);
        $data['reply_to_message'] = $replyPayload;
        $data['reaction_summary'] = $reactionSummary;
        $data['my_reaction'] = $myReaction;
        $data['read_by_user_ids'] = $reads->pluck('user_id')->unique()->values()->all();

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function replyToMessagePayload(ChatMessage $message): ?array
    {
        if (! $message->reply_to) {
            return null;
        }

        $parent = $message->relationLoaded('replyTo') ? $message->replyTo : null;

        if (! $parent) {
            return [
                'id' => (int) $message->reply_to,
                'sender_id' => null,
                'message' => null,
                'is_unavailable' => true,
            ];
        }

        if ($parent->deleted_at !== null) {
            return [
                'id' => $parent->id,
                'sender_id' => $parent->sender_id,
                'message' => null,
                'is_unavailable' => true,
            ];
        }

        $text = (string) $parent->message;
        if (mb_strlen($text) > 240) {
            $text = mb_substr($text, 0, 240).'…';
        }

        return [
            'id' => $parent->id,
            'sender_id' => $parent->sender_id,
            'message' => $text,
            'created_at' => optional($parent->created_at)->toIso8601String(),
            'is_unavailable' => false,
        ];
    }

    private function sendPushForNewMessage(int $conversationId, ChatMessage $message, int $senderId, string $senderName): void
    {
        $recipientIds = ChatConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->whereNull('left_at')
            ->where('user_id', '!=', $senderId)
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        $recipients = User::query()->whereIn('id', $recipientIds->all())->get(['id', 'push_subscription']);
        $preview = Str::limit((string) $message->message, 120);
        $title = "New message from {$senderName}";

        foreach ($recipients as $recipient) {
            $this->firebasePushService->sendToUser($recipient, $title, $preview, [
                'kind' => 'chat_message',
                'conversation_id' => (string) $conversationId,
                'message_id' => (string) $message->id,
                'sender_id' => (string) $senderId,
            ]);
        }
    }
}
