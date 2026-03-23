<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiMessage;
use App\Models\Ai\AiUserUsage;
use App\Services\AiAdviserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiAdviserController extends Controller
{
    use ApiResponse;

    protected int $historyLimit;

    protected int $historyMessageMaxChars;

    public function __construct(
        protected AiAdviserService $ai,
    ) {
        $this->historyLimit = (int) config('gemini.history_limit', 10);
        $this->historyMessageMaxChars = (int) config('gemini.history_message_max_chars', 800);
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
            $remaining = max(0, $limit - (int) $usage->total_tokens);

            return $this->error(
                'AI token limit exceeded. Please upgrade your AI subscription plan.',
                402,
                [
                    'tokens_used' => (int) $usage->total_tokens,
                    'tokens_limit' => $limit,
                    'tokens_remaining' => $remaining,
                ]
            );
        }

        $usage->increment('total_tokens', $newTokens);

        return null;
    }

    /**
     * Build a consistent message object for API responses.
     * Assistant messages include structured content_sections for easy UI rendering.
     */
    protected function formatMessageForResponse(AiMessage $message, int $authUserId, ?array $sections = null): array
    {
        $role = $message->user_id === $authUserId ? 'user' : 'assistant';

        $formatted = [
            'id' => $message->id,
            'role' => $role,
            'content' => $message->content,
            'sent_at' => $message->created_at?->toIso8601String(),
        ];

        if ($role === 'assistant') {
            $formatted['content_sections'] = $sections ?? $this->ai->buildSections($message->content);
        }

        return $formatted;
    }

    /**
     * Build a consistent conversation summary object for API responses.
     */
    protected function formatConversationSummary(
        AiConversation $conversation,
        ?int $messageCount = null,
        ?string $lastMessageContent = null
    ): array
    {
        return [
            'id' => $conversation->id,
            'subject' => $conversation->subject,
            'status' => $conversation->status,
            'total_messages' => (int) ($messageCount ?? 0),
            'last_message_preview' => $lastMessageContent
                ? Str::limit($lastMessageContent, 120)
                : null,
            'started_at' => $conversation->created_at?->toIso8601String(),
            'last_active_at' => $conversation->last_used_at
                ? $conversation->last_used_at->toIso8601String()
                : $conversation->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Build a token-usage snapshot for the current user.
     */
    protected function buildTokenUsageSnapshot($user): array
    {
        $limit = $this->getUserTokenLimit($user);

        $usage = AiUserUsage::firstOrCreate(
            ['user_id' => $user->id],
            ['total_tokens' => 0],
        );

        $used = (int) $usage->total_tokens;
        $remaining = max(0, $limit - $used);
        $percentage = $limit > 0 ? min(100, round(($used / $limit) * 100, 2)) : 0.0;

        return [
            'tokens_used' => $used,
            'tokens_limit' => $limit,
            'tokens_remaining' => $remaining,
            'usage_percentage' => $percentage,
        ];
    }

    // ------------------------------------------------------------------
    //  Endpoints
    // ------------------------------------------------------------------

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $aiConversation = AiConversation::create([
            'user_id' => $user->id,
            'subject' => $validated['subject'] ?? null,
            'status' => 'active',
            'model' => env('AI_ADVISER_PROVIDER', 'gemini') === 'grok'
                ? env('GROK_MODEL_ID', 'grok-2-latest')
                : config('gemini.model_id', 'gemini-2.5-flash-lite'),
            'purpose' => 'ai_adviser',
            'settings' => [],
        ]);

        $userMessage = AiMessage::create([
            'ai_conversation_id' => $aiConversation->id,
            'user_id' => $user->id,
            'content' => $validated['message'],
            'role' => 'user',
        ]);

        $result = $this->ai->generateReply($validated['message']);

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

        $aiUserId = (int) (config('gemini.system_user_id') ?: $user->id);

        $assistantMessage = AiMessage::create([
            'ai_conversation_id' => $aiConversation->id,
            'user_id' => $aiUserId,
            'content' => $result['text'],
            'role' => 'assistant',
            'prompt_tokens' => (int) ($result['usage']['promptTokenCount'] ?? 0),
            'completion_tokens' => (int) ($result['usage']['candidatesTokenCount'] ?? 0),
            'total_tokens' => $totalTokens,
            'raw_request' => null,
            'raw_response' => $result['raw'],
        ]);

        $aiConversation->update(['last_used_at' => now()]);

        return $this->success('AI adviser conversation started.', [
            'conversation' => [
                'id' => $aiConversation->id,
                'subject' => $aiConversation->subject,
                'status' => $aiConversation->status,
                'started_at' => $aiConversation->created_at?->toIso8601String(),
            ],
            'messages' => [
                $this->formatMessageForResponse($userMessage, $user->id),
                $this->formatMessageForResponse($assistantMessage, $user->id, $result['sections']),
            ],
            'token_usage' => $this->buildTokenUsageSnapshot($user),
        ]);
    }

    public function reply(Request $request, AiConversation $aiConversation): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);
        $user = $request->user();

        if ($aiConversation->user_id !== $user->id || $aiConversation->purpose !== 'ai_adviser') {
            return $this->forbidden('You are not part of this conversation.');
        }

        $history = $aiConversation->messages()
            ->latest()
            ->take($this->historyLimit)
            ->get()
            ->sortBy('created_at')
            ->map(function (AiMessage $message) use ($user) {
                $role = $message->user_id === $user->id ? 'user' : 'assistant';

                return [
                    'role' => $role,
                    'content' => Str::limit($message->content, $this->historyMessageMaxChars),
                ];
            })
            ->values()
            ->all();

        $userMessage = AiMessage::create([
            'ai_conversation_id' => $aiConversation->id,
            'user_id' => $user->id,
            'content' => $validated['message'],
            'role' => 'user',
        ]);

        // Provider is chosen only via env in AiAdviserService.
        $result = $this->ai->generateReply($validated['message'], $history);

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

        $aiUserId = (int) (config('gemini.system_user_id') ?: $user->id);

        $assistantMessage = AiMessage::create([
            'ai_conversation_id' => $aiConversation->id,
            'user_id' => $aiUserId,
            'content' => $result['text'],
            'role' => 'assistant',
            'prompt_tokens' => (int) ($result['usage']['promptTokenCount'] ?? 0),
            'completion_tokens' => (int) ($result['usage']['candidatesTokenCount'] ?? 0),
            'total_tokens' => $totalTokens,
            'raw_request' => null,
            'raw_response' => $result['raw'],
        ]);

        $aiConversation->update(['last_used_at' => now()]);

        $totalMessages = $aiConversation->messages()->count();

        return $this->success('AI adviser reply received.', [
            'conversation' => [
                'id' => $aiConversation->id,
                'subject' => $aiConversation->subject,
                'status' => $aiConversation->status,
                'total_messages' => $totalMessages,
            ],
            'user_message' => $this->formatMessageForResponse($userMessage, $user->id),
            'assistant_message' => $this->formatMessageForResponse($assistantMessage, $user->id, $result['sections']),
            'token_usage' => $this->buildTokenUsageSnapshot($user),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $paginator = AiConversation::where('user_id', $user->id)
            ->where('purpose', 'ai_adviser')
            ->withCount('messages')
            ->orderByDesc('last_used_at')
            ->paginate(15);

        $conversationIds = collect($paginator->items())->pluck('id')->all();
        $latestMessageByConversation = $this->latestMessageContentByConversationIds($conversationIds);

        $conversations = collect($paginator->items())->map(
            fn (AiConversation $c) => $this->formatConversationSummary(
                $c,
                (int) ($c->messages_count ?? 0),
                $latestMessageByConversation->get($c->id)
            )
        );

        return $this->success('AI adviser conversations fetched.', [
            'conversations' => $conversations,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    protected function latestMessageContentByConversationIds(array $conversationIds): Collection
    {
        if (empty($conversationIds)) {
            return collect();
        }

        return AiMessage::query()
            ->select(['ai_conversation_id', 'content'])
            ->whereIn('ai_conversation_id', $conversationIds)
            ->orderBy('ai_conversation_id')
            ->orderByDesc('created_at')
            ->get()
            ->unique('ai_conversation_id')
            ->mapWithKeys(fn (AiMessage $message) => [$message->ai_conversation_id => $message->content]);
    }

    public function show(Request $request, AiConversation $aiConversation): JsonResponse
    {
        $user = $request->user();

        if ($aiConversation->user_id !== $user->id || $aiConversation->purpose !== 'ai_adviser') {
            return $this->forbidden('You are not part of this conversation.');
        }

        $messages = $aiConversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn (AiMessage $m) => $this->formatMessageForResponse($m, $user->id))
            ->values();

        return $this->success('AI adviser conversation details.', [
            'conversation' => [
                'id' => $aiConversation->id,
                'subject' => $aiConversation->subject,
                'status' => $aiConversation->status,
                'total_messages' => $messages->count(),
                'started_at' => $aiConversation->created_at?->toIso8601String(),
                'last_active_at' => $aiConversation->last_used_at
                    ? $aiConversation->last_used_at->toIso8601String()
                    : $aiConversation->updated_at?->toIso8601String(),
            ],
            'messages' => $messages,
        ]);
    }

    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();
        $snapshot = $this->buildTokenUsageSnapshot($user);

        return $this->success('AI adviser token usage.', [
            'token_usage' => $snapshot,
            'is_limit_reached' => $snapshot['tokens_remaining'] <= 0,
        ]);
    }
}

