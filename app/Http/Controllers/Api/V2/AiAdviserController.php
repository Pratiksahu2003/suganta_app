<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiMessage;
use App\Models\Ai\AiUserUsage;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\GeminiAiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAdviserController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected GeminiAiService $gemini,
    ) {
    }

    protected function getUserTokenLimit($user): int
    {
        $freeLimit = (int) config('gemini.free_token_limit', 100000);
        $sType = (int) config('gemini.subscription_type', 2);

        $activeSubscription = $user->activeSubscriptionForType($sType)->with('plan')->first();

        if ($activeSubscription && $activeSubscription->plan) {
            $features = $activeSubscription->plan->features ?? [];
            if (is_array($features) && isset($features['ai_tokens'])) {
                return (int) $features['ai_tokens'];
            }
        }

        return $freeLimit;
    }

    protected function ensureWithinTokenLimit($user, int $newTokens): ?JsonResponse
    {
        $limit = $this->getUserTokenLimit($user);

        $usage = AiUserUsage::firstOrCreate(
            ['user_id' => $user->id],
            ['total_tokens' => 0],
        );

        if ($usage->total_tokens + $newTokens > $limit) {
            return $this->error(
                'AI token limit exceeded. Please upgrade your AI subscription plan.',
                402
            );
        }

        $usage->increment('total_tokens', $newTokens);

        return null;
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        $conversation = Conversation::create([
            'initiator_id' => $user->id,
            'participant_id' => $user->id,
            'subject' => $validated['subject'] ?? null,
            'type' => 'general',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $aiConversation = AiConversation::create([
            'conversation_id' => $conversation->id,
            'model' => config('gemini.model_id', 'gemini-2.5-flash-lite'),
            'purpose' => 'ai_adviser',
            'settings' => [],
        ]);

        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $validated['message'],
            'type' => 'text',
        ]);

        AiMessage::create([
            'message_id' => $userMessage->id,
            'role' => 'user',
        ]);

        $result = $this->gemini->generateReply($validated['message']);

        $totalTokens = 0;
        if (is_array($result['usage'] ?? null) && isset($result['usage']['totalTokenCount'])) {
            $totalTokens = (int) $result['usage']['totalTokenCount'];
        }

        if ($totalTokens > 0) {
            $limitError = $this->ensureWithinTokenLimit($user, $totalTokens);
            if ($limitError) {
                return $limitError;
            }
        }

        $replyText = $result['text'];

        $aiUserId = config('gemini.system_user_id') ?: $user->id;

        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $aiUserId,
            'content' => $replyText,
            'type' => 'system',
        ]);

        AiMessage::create([
            'message_id' => $assistantMessage->id,
            'role' => 'assistant',
            'prompt_tokens' => (int) ($result['usage']['promptTokenCount'] ?? 0),
            'completion_tokens' => (int) ($result['usage']['candidatesTokenCount'] ?? 0),
            'total_tokens' => $totalTokens,
            'raw_request' => null,
            'raw_response' => $result['raw'],
        ]);

        $aiConversation->update([
            'last_used_at' => now(),
        ]);

        return $this->success('AI adviser response generated.', [
            'conversation_id' => $conversation->id,
            'message' => $assistantMessage->content,
        ]);
    }

    public function reply(Request $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $user = Auth::user();

        if (! $conversation->involvesUser($user->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        $aiConversation = AiConversation::firstOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'model' => config('gemini.model_id', 'gemini-2.5-flash-lite'),
                'purpose' => 'ai_adviser',
                'settings' => [],
            ],
        );

        $history = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(function (Message $message) use ($user) {
                $role = $message->sender_id === $user->id ? 'user' : 'assistant';

                return [
                    'role' => $role,
                    'content' => $message->content,
                ];
            })
            ->all();

        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $validated['message'],
            'type' => 'text',
        ]);

        AiMessage::create([
            'message_id' => $userMessage->id,
            'role' => 'user',
        ]);

        $result = $this->gemini->generateReply($validated['message'], $history);

        $totalTokens = 0;
        if (is_array($result['usage'] ?? null) && isset($result['usage']['totalTokenCount'])) {
            $totalTokens = (int) $result['usage']['totalTokenCount'];
        }

        if ($totalTokens > 0) {
            $limitError = $this->ensureWithinTokenLimit($user, $totalTokens);
            if ($limitError) {
                return $limitError;
            }
        }

        $replyText = $result['text'];

        $aiUserId = config('gemini.system_user_id') ?: $user->id;

        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $aiUserId,
            'content' => $replyText,
            'type' => 'system',
        ]);

        AiMessage::create([
            'message_id' => $assistantMessage->id,
            'role' => 'assistant',
            'prompt_tokens' => (int) ($result['usage']['promptTokenCount'] ?? 0),
            'completion_tokens' => (int) ($result['usage']['candidatesTokenCount'] ?? 0),
            'total_tokens' => $totalTokens,
            'raw_request' => null,
            'raw_response' => $result['raw'],
        ]);

        $conversation->update([
            'last_message_at' => now(),
        ]);

        $aiConversation->update([
            'last_used_at' => now(),
        ]);

        return $this->success('AI adviser response generated.', [
            'conversation_id' => $conversation->id,
            'message' => $assistantMessage->content,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $conversations = Conversation::where('initiator_id', $user->id)
            ->whereHas(\App\Models\Ai\AiConversation::class, function ($query) {
                $query->where('purpose', 'ai_adviser');
            })
            ->orderByDesc('last_message_at')
            ->paginate(15);

        return $this->paginated($conversations, 'AI adviser conversations fetched.');
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (! $conversation->involvesUser($user->id)) {
            return $this->forbidden('You are not part of this conversation.');
        }

        $messages = $conversation->messages()->orderBy('created_at')->get()->map(function (Message $message) use ($user) {
            $role = $message->sender_id === $user->id ? 'user' : 'assistant';

            return [
                'id' => $message->id,
                'role' => $role,
                'content' => $message->content,
                'created_at' => $message->created_at,
            ];
        });

        return $this->success('AI adviser conversation fetched.', [
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }
}

