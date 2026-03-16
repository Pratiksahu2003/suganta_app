<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiMessage;
use App\Models\Ai\AiUserUsage;
use App\Services\GeminiAiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AiAdviserController extends Controller
{
    use ApiResponse;

    /**
     * Maximum number of previous messages to include in the AI context history
     * when generating a reply. Kept intentionally small to reduce token usage.
     */
    protected int $historyLimit;

    /**
     * Maximum characters per history message sent to the AI model.
     * Older/long messages are trimmed to keep prompts lightweight.
     */
    protected int $historyMessageMaxChars;

    public function __construct(
        protected GeminiAiService $gemini,
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

        $user = $request->user();

        $aiConversation = AiConversation::create([
            'user_id' => $user->id,
            'subject' => $validated['subject'] ?? null,
            'status' => 'active',
            'model' => config('gemini.model_id', 'gemini-2.5-flash-lite'),
            'purpose' => 'ai_adviser',
            'settings' => [],
        ]);

        $userMessage = AiMessage::create([
            'ai_conversation_id' => $aiConversation->id,
            'user_id' => $user->id,
            'content' => $validated['message'],
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

        $aiUserId = (int) (config('gemini.system_user_id') ?: $user->id);

        $assistantMessage = AiMessage::create([
            'ai_conversation_id' => $aiConversation->id,
            'user_id' => $aiUserId,
            'content' => $replyText,
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
            'conversation_id' => $aiConversation->id,
            'message' => $assistantMessage->content,
        ]);
    }

    public function reply(Request $request, AiConversation $aiConversation): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);
        $user = $request->user();

        // Ensure the conversation strictly belongs to the authenticated user
        // and is for the AI adviser purpose.
        if ($aiConversation->user_id !== $user->id || $aiConversation->purpose !== 'ai_adviser') {
            return $this->forbidden('You are not part of this conversation.');
        }

        // Build a trimmed history with only the most recent messages to reduce
        // the number of tokens sent to the AI model.
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

        AiMessage::create([
            'ai_conversation_id' => $aiConversation->id,
            'user_id' => $user->id,
            'content' => $validated['message'],
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

        $aiUserId = (int) (config('gemini.system_user_id') ?: $user->id);

        $assistantMessage = AiMessage::create([
            'ai_conversation_id' => $aiConversation->id,
            'user_id' => $aiUserId,
            'content' => $replyText,
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
            'conversation_id' => $aiConversation->id,
            'message' => $assistantMessage->content,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = AiConversation::where('user_id', $user->id)
            ->where('purpose', 'ai_adviser')
            ->orderByDesc('last_used_at')
            ->paginate(15);

        return $this->paginated($conversations, 'AI adviser conversations fetched.');
    }

    public function show(Request $request, AiConversation $aiConversation): JsonResponse
    {
        $user = $request->user();

        // Ensure the conversation strictly belongs to the authenticated user
        // and is for the AI adviser purpose.
        if ($aiConversation->user_id !== $user->id || $aiConversation->purpose !== 'ai_adviser') {
            return $this->forbidden('You are not part of this conversation.');
        }

        $messages = $aiConversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(function (AiMessage $message) use ($user) {
                $role = $message->user_id === $user->id ? 'user' : 'assistant';

                return [
                    'id' => $message->id,
                    'role' => $role,
                    'content' => $message->content,
                    'created_at' => $message->created_at,
                ];
            });

        return $this->success('AI adviser conversation fetched.', [
            'conversation_id' => $aiConversation->id,
            'messages' => $messages,
        ]);
    }
}

