<?php

namespace App\Http\Controllers\Api\V3\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat\ChatConversation;
use App\Models\Chat\ChatConversationParticipant;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    use ApiResponse;

    public function searchUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $auth = $request->user();
        $query = trim($validated['q']);
        $limit = (int) ($validated['limit'] ?? 20);
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
                'phone' => $user->phone,
                'profile_image' => storage_file_url($user->profile?->profile_image),
                'has_private_conversation' => $privateConversationId !== null,
                'private_conversation_id' => $privateConversationId,
            ];
        })->values();

        return $this->success('Users fetched successfully.', [
            'query' => $query,
            'total' => $users->count(),
            'users' => $users,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = ChatConversation::query()
            ->whereHas('participants', function ($query) use ($user): void {
                $query->where('user_id', $user->id)->whereNull('left_at');
            })
            ->withCount(['messages as total_messages'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20);

        return $this->success('Chat conversations fetched successfully.', $conversations);
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
                return $this->success('Private chat already exists.', [
                    'conversation' => ChatConversation::find($existingConversationId),
                ]);
            }
        }

        $conversation = DB::connection('ai_mysql')->transaction(function () use ($validated, $user, $participantIds) {
            $conversation = ChatConversation::create([
                'type' => $validated['type'],
                'title' => $validated['title'] ?? null,
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

        return $this->created([
            'conversation' => $conversation->fresh(),
        ], 'Chat conversation created successfully.');
    }

    public function show(Request $request, int $conversation): JsonResponse
    {
        $user = $request->user();
        $chat = ChatConversation::findOrFail($conversation);

        if (! $this->isParticipant($chat->id, $user->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        $participants = ChatConversationParticipant::where('conversation_id', $chat->id)
            ->whereNull('left_at')
            ->orderBy('id')
            ->get(['id', 'conversation_id', 'user_id', 'role', 'joined_at']);

        return $this->success('Chat conversation fetched successfully.', [
            'conversation' => $chat,
            'participants' => $participants,
        ]);
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

        return $this->success('You have left the conversation.');
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
