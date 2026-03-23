<?php

namespace App\Http\Controllers\Api\V3\Chat;

use App\Events\Chat\ConversationReadStateUpdated;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V3\Chat\Concerns\InteractsWithChatCache;
use App\Models\Chat\ChatConversation;
use App\Models\Chat\ChatConversationParticipant;
use App\Models\Chat\ChatMessage;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    use ApiResponse, InteractsWithChatCache;

    public function searchUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $auth = $request->user();
        $query = trim($validated['q']);
        $limit = (int) ($validated['limit'] ?? 20);
        $version = $this->chatUserVersion((int) $auth->id);
        $cacheKey = 'chat:v3:search_users:'.$auth->id.':'.$version.':'.sha1(json_encode([
            'q' => mb_strtolower($query),
            'limit' => $limit,
        ], JSON_UNESCAPED_UNICODE));

        $payload = \Illuminate\Support\Facades\Cache::remember($cacheKey, $this->chatCacheTtlSeconds(), function () use ($auth, $query, $limit) {
            $phoneQuery = preg_replace('/\D+/', '', $query) ?? '';
            $normalizedPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')";

            $usersQuery = User::query()
                ->where('id', '!=', $auth->id)
                ->where(function ($builder) use ($query, $phoneQuery): void {
                    $builder->where('name', 'like', '%'.$query.'%');

                    if ($phoneQuery !== '') {
                        $builder->orWhere('phone', 'like', '%'.$phoneQuery.'%');
                        $builder->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') like ?",
                            ['%'.$phoneQuery.'%']
                        );
                    }
                })
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$query.'%']);

            if ($phoneQuery !== '') {
                $usersQuery->orderByRaw('CASE WHEN '.$normalizedPhoneSql.' LIKE ? THEN 0 ELSE 1 END', [$phoneQuery.'%']);
            }

            $users = $usersQuery
                ->with('profile:id,user_id,profile_image')
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'phone']);

            $existingPrivateMap = collect();
            if ($users->isNotEmpty()) {
                $otherUserIds = $users->pluck('id')->values();
                $existingPrivateMap = ChatConversation::query()
                    ->select(['chat_conversations.id', 'other.user_id as other_user_id'])
                    ->join('chat_conversation_participants as me', 'me.conversation_id', '=', 'chat_conversations.id')
                    ->join('chat_conversation_participants as other', 'other.conversation_id', '=', 'chat_conversations.id')
                    ->where('chat_conversations.type', 'private')
                    ->whereNull('me.left_at')
                    ->whereNull('other.left_at')
                    ->where('me.user_id', $auth->id)
                    ->whereIn('other.user_id', $otherUserIds)
                    ->pluck('chat_conversations.id', 'other_user_id');
            }

            $users = $users->map(function (User $user) use ($existingPrivateMap) {
                $privateConversationId = $existingPrivateMap->get($user->id);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => mask_phone_for_display($user->phone),
                    'profile_image' => storage_file_url($user->profile?->profile_image),
                    'has_private_conversation' => $privateConversationId !== null,
                    'private_conversation_id' => $privateConversationId,
                ];
            })->values();

            return [
                'query' => $query,
                'total' => $users->count(),
                'users' => $users,
            ];
        });

        return $this->success('Users fetched successfully.', $payload);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'folder' => ['sometimes', 'in:inbox,archived,all'],
        ]);

        $user = $request->user();
        $folder = (string) $request->query('folder', 'inbox');
        $page = max(1, (int) $request->query('page', 1));
        $userVersion = $this->chatUserVersion((int) $user->id);
        $cacheKey = 'chat:v3:conversations:index:'.$user->id.':'.$userVersion.':'.sha1(json_encode([
            'folder' => $folder,
            'page' => $page,
        ], JSON_UNESCAPED_UNICODE));

        $payload = \Illuminate\Support\Facades\Cache::remember($cacheKey, $this->chatCacheTtlSeconds(), function () use ($user, $folder) {
            $paginator = ChatConversation::query()
                ->whereHas('participants', function ($query) use ($user, $folder): void {
                    $query->where('user_id', $user->id)->whereNull('left_at');
                    if ($folder === 'inbox') {
                        $query->whereNull('archived_at');
                    } elseif ($folder === 'archived') {
                        $query->whereNotNull('archived_at');
                    }
                })
                ->withCount(['messages as total_messages'])
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->paginate(20);

            $presented = $this->presentConversationCollection($paginator->getCollection(), $user);
            $paginator->setCollection($presented);

            return array_merge(
                ['folder' => $folder],
                $paginator->toArray()
            );
        });

        return $this->success('Chat conversations fetched successfully.', $payload);
    }

    public function update(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'muted' => ['sometimes', 'boolean'],
            'archived' => ['sometimes', 'boolean'],
        ]);

        if ($validated === []) {
            return $this->validationError(['muted' => ['Provide muted and/or archived.']]);
        }

        $auth = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isParticipant($chat->id, $auth->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        $participant = ChatConversationParticipant::query()
            ->where('conversation_id', $chat->id)
            ->where('user_id', $auth->id)
            ->whereNull('left_at')
            ->firstOrFail();

        if (array_key_exists('muted', $validated)) {
            $participant->muted_at = $validated['muted'] ? now() : null;
        }
        if (array_key_exists('archived', $validated)) {
            $participant->archived_at = $validated['archived'] ? now() : null;
        }
        $participant->save();
        $this->flushConversationReadCaches($chat->id, [$auth->id]);

        return $this->success('Conversation updated successfully.', [
            'my_membership' => $this->myMembershipPayload($participant),
        ]);
    }

    public function markRead(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'message_id' => ['nullable', 'integer'],
        ]);

        $auth = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isParticipant($chat->id, $auth->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        $participant = ChatConversationParticipant::query()
            ->where('conversation_id', $chat->id)
            ->where('user_id', $auth->id)
            ->whereNull('left_at')
            ->firstOrFail();

        $tipId = null;
        if (! empty($validated['message_id'])) {
            $msg = ChatMessage::query()
                ->where('id', $validated['message_id'])
                ->where('conversation_id', $chat->id)
                ->whereNull('deleted_at')
                ->first();
            if (! $msg) {
                return $this->validationError(['message_id' => ['Message not found in this conversation.']]);
            }
            $tipId = $msg->id;
        } else {
            $tipId = $chat->last_message_id;
        }

        $readAt = now();
        if ($tipId !== null) {
            $current = $participant->last_read_message_id;
            if ($current === null || $tipId > $current) {
                $participant->last_read_message_id = $tipId;
                $participant->save();
                $this->flushConversationReadCaches($chat->id, [$auth->id]);
            }
        }

        broadcast(new ConversationReadStateUpdated(
            $chat->id,
            $auth->id,
            $participant->last_read_message_id,
            $readAt->toIso8601String()
        ))->toOthers();

        return $this->success('Conversation marked as read.', [
            'last_read_message_id' => $participant->last_read_message_id,
            'read_at' => $readAt->toIso8601String(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:private,group'],
            'title' => ['nullable', 'string', 'max:255'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $user = $request->user();
        $participantIds = collect($validated['participants'])
            ->filter(fn (int $id) => $id !== $user->id)
            ->unique()
            ->values();

        if ($validated['type'] === 'private' && $participantIds->count() !== 1) {
            return $this->validationError(['participants' => ['Private chat requires exactly one other participant.']]);
        }

        if ($validated['type'] === 'group' && $participantIds->count() < 2) {
            return $this->validationError(['participants' => ['Group chat requires at least two other participants.']]);
        }

        if ($validated['type'] === 'private') {
            $existingConversationId = ChatConversation::query()
                ->select('chat_conversations.id')
                ->join('chat_conversation_participants as me', 'me.conversation_id', '=', 'chat_conversations.id')
                ->join('chat_conversation_participants as other', 'other.conversation_id', '=', 'chat_conversations.id')
                ->where('chat_conversations.type', 'private')
                ->whereNull('me.left_at')
                ->whereNull('other.left_at')
                ->where('me.user_id', $user->id)
                ->where('other.user_id', $participantIds->first())
                ->value('chat_conversations.id');

            if ($existingConversationId !== null) {
                $existing = ChatConversation::findOrFail($existingConversationId);

                return $this->success('Private chat already exists.', [
                    'conversation' => $this->presentConversationDetail($existing, $user),
                ]);
            }
        }

        $conversation = DB::connection('ai_mysql')->transaction(function () use ($validated, $user, $participantIds) {
            $conversation = ChatConversation::create([
                'type' => $validated['type'],
                'title' => $validated['type'] === 'private' ? null : ($validated['title'] ?? null),
                'created_by' => $user->id,
            ]);

            ChatConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'joined_at' => now(),
            ]);

            foreach ($participantIds as $participantId) {
                ChatConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $participantId,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
            }

            return $conversation;
        });
        foreach ($participantIds->push($user->id) as $participantUserId) {
            $this->bumpChatUserVersion((int) $participantUserId);
        }
        $this->bumpChatConversationVersion((int) $conversation->id);

        return $this->created([
            'conversation' => $this->presentConversationDetail($conversation->fresh(), $user),
        ], 'Chat conversation created successfully.');
    }

    public function show(Request $request, int $conversation): JsonResponse
    {
        $user = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isParticipant($chat->id, $user->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        $userVersion = $this->chatUserVersion((int) $user->id);
        $conversationVersion = $this->chatConversationVersion((int) $chat->id);
        $cacheKey = 'chat:v3:conversations:show:'.$chat->id.':'.$user->id.':'.$userVersion.':'.$conversationVersion;

        $payload = \Illuminate\Support\Facades\Cache::remember($cacheKey, $this->chatCacheTtlSeconds(), function () use ($chat, $user) {
            return [
                'conversation' => $this->presentConversationDetail($chat, $user),
                'participants' => $this->enrichedParticipants($chat)->values(),
                'my_membership' => $this->myMembershipForChat($chat, $user),
            ];
        });

        return $this->success('Chat conversation fetched successfully.', $payload);
    }

    public function addParticipant(Request $request, int $conversation): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $auth = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isAdmin($chat->id, $auth->id)) {
            return $this->forbidden('Only admins can add participants.');
        }

        if ($chat->type !== 'group') {
            return $this->error('Participants can only be added to group conversations.', 422);
        }

        if ((int) $validated['user_id'] === (int) $auth->id) {
            return $this->error('You are already part of this conversation.', 422);
        }

        ChatConversationParticipant::updateOrCreate(
            ['conversation_id' => $chat->id, 'user_id' => $validated['user_id']],
            ['role' => 'member', 'joined_at' => now(), 'left_at' => null]
        );
        $this->flushConversationReadCaches($chat->id, [$auth->id, (int) $validated['user_id']]);

        return $this->success('Participant added successfully.');
    }

    public function removeParticipant(Request $request, int $conversation, int $user): JsonResponse
    {
        $auth = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isAdmin($chat->id, $auth->id)) {
            return $this->forbidden('Only admins can remove participants.');
        }

        if ($chat->type !== 'group') {
            return $this->error('Participants can only be removed from group conversations.', 422);
        }

        if ((int) $user === (int) $auth->id) {
            return $this->error('Use leave endpoint to leave the conversation yourself.', 422);
        }

        $affected = ChatConversationParticipant::where('conversation_id', $chat->id)
            ->where('user_id', $user)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        if ($affected === 0) {
            return $this->notFound('Participant not found in this conversation.');
        }
        $this->flushConversationReadCaches($chat->id, [$auth->id, $user]);

        return $this->success('Participant removed successfully.');
    }

    public function leave(Request $request, int $conversation): JsonResponse
    {
        $auth = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        $affected = ChatConversationParticipant::where('conversation_id', $chat->id)
            ->where('user_id', $auth->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        if ($affected === 0) {
            return $this->notFound('You are not an active participant in this conversation.');
        }
        $this->flushConversationReadCaches($chat->id, [$auth->id]);

        return $this->success('You have left the conversation.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function peerSummaryFromUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => mask_phone_for_display($user->phone),
            'profile_image' => storage_file_url($user->profile?->profile_image),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function lastMessagePreview(
        ?ChatMessage $message,
        int $authUserId,
        string $authUserName,
        Collection $usersById,
    ): ?array {
        if ($message === null) {
            return null;
        }

        $text = (string) $message->message;
        if (mb_strlen($text) > 200) {
            $text = mb_substr($text, 0, 200).'…';
        }

        $isMine = (int) $message->sender_id === $authUserId;
        $sender = $usersById->get($message->sender_id);
        $senderName = $isMine
            ? $authUserName
            : ($sender instanceof User ? $sender->name : 'User');

        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => $senderName,
            'is_mine' => $isMine,
            'text' => $text,
            'created_at' => optional($message->created_at)->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentConversationForList(
        ChatConversation $conv,
        Collection $participantRows,
        Collection $usersById,
        ?ChatMessage $lastMessage,
        int $authUserId,
        ?ChatConversationParticipant $myParticipant,
        int $unreadCount,
        string $authUserName,
    ): array {
        $base = $conv->toArray();
        $peer = null;

        if ($conv->type === 'private') {
            $otherUserId = null;
            foreach ($participantRows as $p) {
                if ((int) $p->user_id !== $authUserId) {
                    $otherUserId = (int) $p->user_id;
                    break;
                }
            }

            $peerUser = $otherUserId !== null ? $usersById->get($otherUserId) : null;
            if ($peerUser instanceof User) {
                $peer = $this->peerSummaryFromUser($peerUser);
                $displayTitle = $peerUser->name;
            } else {
                $stored = $conv->title ? strtolower(trim((string) $conv->title)) : '';
                $junkTitle = in_array($stored, ['private chat', 'private', 'direct', 'dm'], true);
                $displayTitle = ($conv->title && ! $junkTitle) ? (string) $conv->title : 'Chat';
            }

            $base['title'] = $displayTitle;
            $base['display_title'] = $displayTitle;
            $base['peer'] = $peer;
        } else {
            $displayTitle = $conv->title ? (string) $conv->title : 'Group chat';
            $base['title'] = $displayTitle;
            $base['display_title'] = $displayTitle;
            $base['peer'] = null;
        }

        $base['user_name'] = $conv->type === 'private'
            ? ($peer !== null ? $peer['name'] : $base['display_title'])
            : $base['display_title'];

        $base['last_message'] = $this->lastMessagePreview($lastMessage, $authUserId, $authUserName, $usersById);
        $base['unread_count'] = $unreadCount;
        $base['muted'] = $myParticipant !== null && $myParticipant->muted_at !== null;
        $base['archived'] = $myParticipant !== null && $myParticipant->archived_at !== null;
        $base['last_read_message_id'] = $myParticipant?->last_read_message_id;

        return $base;
    }

    /**
     * @param  Collection<int, ChatConversation>  $conversations
     * @return Collection<int, array<string, mixed>>
     */
    protected function presentConversationCollection(Collection $conversations, User $auth): Collection
    {
        if ($conversations->isEmpty()) {
            return collect();
        }

        $convIds = $conversations->pluck('id')->all();

        $participantsByConv = ChatConversationParticipant::query()
            ->whereIn('conversation_id', $convIds)
            ->whereNull('left_at')
            ->get()
            ->groupBy('conversation_id');

        $myParticipants = ChatConversationParticipant::query()
            ->whereIn('conversation_id', $convIds)
            ->where('user_id', $auth->id)
            ->whereNull('left_at')
            ->get()
            ->keyBy('conversation_id');

        $unreadCounts = $this->unreadCountsByConversation($convIds, $auth->id);

        $userIds = $participantsByConv->flatten()->pluck('user_id')->unique()->values()->all();

        $lastMessageIds = $conversations->pluck('last_message_id')->filter()->unique()->values()->all();
        $lastMessages = $lastMessageIds === []
            ? collect()
            : ChatMessage::query()->whereIn('id', $lastMessageIds)->get()->keyBy('id');

        $lastSenderIds = $lastMessages->pluck('sender_id')->unique()->filter()->values()->all();
        $allUserIds = collect($userIds)->merge($lastSenderIds)->unique()->values()->all();

        $usersById = $allUserIds === []
            ? collect()
            : User::query()
                ->whereIn('id', $allUserIds)
                ->with('profile:id,user_id,profile_image')
                ->get(['id', 'name', 'phone'])
                ->keyBy('id');

        return $conversations->map(function (ChatConversation $conv) use ($participantsByConv, $usersById, $lastMessages, $auth, $myParticipants, $unreadCounts) {
            $rows = $participantsByConv->get($conv->id, collect());
            $last = $conv->last_message_id ? $lastMessages->get($conv->last_message_id) : null;
            $mine = $myParticipants->get($conv->id);
            $unread = (int) $unreadCounts->get($conv->id, 0);

            return $this->presentConversationForList($conv, $rows, $usersById, $last, $auth->id, $mine, $unread, (string) $auth->name);
        });
    }

    /**
     * @param  array<int|string, int>  $convIds
     * @return Collection<int|string, int>
     */
    protected function unreadCountsByConversation(array $convIds, int $authUserId): Collection
    {
        if ($convIds === []) {
            return collect();
        }

        return DB::connection('ai_mysql')
            ->table('chat_messages as m')
            ->join('chat_conversation_participants as p', function ($join) use ($authUserId): void {
                $join->on('p.conversation_id', '=', 'm.conversation_id')
                    ->where('p.user_id', '=', $authUserId)
                    ->whereNull('p.left_at');
            })
            ->whereIn('m.conversation_id', $convIds)
            ->whereNull('m.deleted_at')
            ->where('m.sender_id', '!=', $authUserId)
            ->where(function ($q): void {
                $q->whereNull('p.last_read_message_id')
                    ->orWhereColumn('m.id', '>', 'p.last_read_message_id');
            })
            ->groupBy('m.conversation_id')
            ->selectRaw('m.conversation_id, COUNT(*) as c')
            ->pluck('c', 'conversation_id');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function myMembershipForChat(ChatConversation $chat, User $auth): ?array
    {
        $p = ChatConversationParticipant::query()
            ->where('conversation_id', $chat->id)
            ->where('user_id', $auth->id)
            ->whereNull('left_at')
            ->first();

        if (! $p) {
            return null;
        }

        return $this->myMembershipPayload($p);
    }

    /**
     * @return array<string, mixed>
     */
    protected function myMembershipPayload(ChatConversationParticipant $participant): array
    {
        return [
            'last_read_message_id' => $participant->last_read_message_id,
            'muted' => $participant->muted_at !== null,
            'archived' => $participant->archived_at !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentConversationDetail(ChatConversation $conv, User $auth): array
    {
        $row = $this->presentConversationCollection(collect([$conv]), $auth)->first();

        return $row ?? [];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function enrichedParticipants(ChatConversation $chat): Collection
    {
        $participants = ChatConversationParticipant::query()
            ->where('conversation_id', $chat->id)
            ->whereNull('left_at')
            ->orderBy('id')
            ->get();

        if ($participants->isEmpty()) {
            return collect();
        }

        $usersById = User::query()
            ->whereIn('id', $participants->pluck('user_id')->unique())
            ->with('profile:id,user_id,profile_image')
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');

        return $participants->map(function (ChatConversationParticipant $p) use ($usersById) {
            $u = $usersById->get($p->user_id);

            return [
                'id' => $p->id,
                'conversation_id' => $p->conversation_id,
                'user_id' => $p->user_id,
                'role' => $p->role,
                'joined_at' => optional($p->joined_at)->toIso8601String(),
                'user' => $u instanceof User ? $this->peerSummaryFromUser($u) : null,
            ];
        });
    }

    protected function isParticipant(int $conversationId, int $userId): bool
    {
        return ChatConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
    }

    protected function isAdmin(int $conversationId, int $userId): bool
    {
        return ChatConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->where('role', 'admin')
            ->whereNull('left_at')
            ->exists();
    }
}
