<?php

namespace App\Http\Controllers\Api\V5;

use App\Http\Controllers\Controller;
use App\Models\Chatbot\ChatbotBotSetting;
use App\Models\Chatbot\ChatbotConversation;
use App\Models\Chatbot\ChatbotFaq;
use App\Models\Chatbot\ChatbotIntent;
use App\Models\Chatbot\ChatbotIntentKeyword;
use App\Models\Chatbot\ChatbotIntentResponse;
use App\Models\Chatbot\ChatbotKeyword;
use App\Models\Chatbot\ChatbotLead;
use App\Models\Chatbot\ChatbotMessage;
use App\Models\Chatbot\ChatbotUser;
use App\Services\Chatbot\AutoReplyService;
use App\Services\Chatbot\ChatbotAnalyticsService;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotAdminController extends Controller
{
    public function __construct(
        protected ChatbotService          $chatbotService,
        protected ChatbotAnalyticsService $analyticsService,
        protected AutoReplyService        $autoReplyService,
    ) {}

    /* ══════════════════════════════════════════════
     * Dashboard & Analytics
     * ══════════════════════════════════════════════ */

    /**
     * GET /api/v5/chatbot/admin/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $period   = $request->query('period', '7d');
        $platform = $request->query('platform');

        $data = $this->analyticsService->getDashboard($period, $platform);
        $data['top_faqs']     = $this->analyticsService->getTopFaqs(5);
        $data['top_keywords'] = $this->analyticsService->getTopKeywords(5);
        $data['platform_breakdown'] = $this->analyticsService->getPlatformBreakdown($period);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /* ══════════════════════════════════════════════
     * Conversations
     * ══════════════════════════════════════════════ */

    /**
     * GET /api/v5/chatbot/admin/conversations
     */
    public function conversations(Request $request): JsonResponse
    {
        $query = ChatbotConversation::with('chatbotUser:id,platform_user_id,platform,name,profile_pic_url')
            ->withCount('messages');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($platform = $request->query('platform')) {
            $query->where('platform', $platform);
        }
        if ($search = $request->query('search')) {
            $query->whereHas('chatbotUser', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $conversations = $query->orderByDesc('last_message_at')
            ->paginate($request->query('per_page', 20));

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    /**
     * GET /api/v5/chatbot/admin/conversations/{id}/messages
     */
    public function messages(int $id, Request $request): JsonResponse
    {
        $conversation = ChatbotConversation::with('chatbotUser')->findOrFail($id);

        $messages = ChatbotMessage::where('conversation_id', $id)
            ->orderBy('created_at')
            ->paginate($request->query('per_page', 50));

        return response()->json([
            'success'      => true,
            'conversation' => $conversation,
            'data'         => $messages,
        ]);
    }

    /**
     * POST /api/v5/chatbot/admin/conversations/{id}/takeover
     */
    public function takeover(int $id, Request $request): JsonResponse
    {
        $conversation = ChatbotConversation::findOrFail($id);
        $conversation->takeoverByHuman($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Conversation taken over by human agent.',
            'data'    => $conversation->fresh(),
        ]);
    }

    /**
     * POST /api/v5/chatbot/admin/conversations/{id}/release
     */
    public function releaseToBot(int $id): JsonResponse
    {
        $conversation = ChatbotConversation::findOrFail($id);
        $conversation->releaseToBot();

        return response()->json([
            'success' => true,
            'message' => 'Conversation released back to bot.',
            'data'    => $conversation->fresh(),
        ]);
    }

    /**
     * POST /api/v5/chatbot/admin/conversations/{id}/reply
     */
    public function sendManualReply(int $id, Request $request): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $message = $this->chatbotService->sendManualReply(
            $id,
            $request->input('message'),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Reply sent.',
            'data'    => $message,
        ]);
    }

    /* ══════════════════════════════════════════════
     * FAQ Management
     * ══════════════════════════════════════════════ */

    /**
     * GET /api/v5/chatbot/admin/faqs
     */
    public function faqIndex(Request $request): JsonResponse
    {
        $query = ChatbotFaq::query();

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $faqs = $query->orderByDesc('priority')->paginate($request->query('per_page', 20));

        return response()->json(['success' => true, 'data' => $faqs]);
    }

    /**
     * POST /api/v5/chatbot/admin/faqs
     */
    public function faqStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question'  => 'required|string|max:500',
            'answer'    => 'required|string',
            'category'  => 'nullable|string|max:100',
            'priority'  => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq = ChatbotFaq::create($validated);
        $this->autoReplyService->clearCache();

        return response()->json(['success' => true, 'data' => $faq], 201);
    }

    /**
     * PUT /api/v5/chatbot/admin/faqs/{id}
     */
    public function faqUpdate(int $id, Request $request): JsonResponse
    {
        $faq = ChatbotFaq::findOrFail($id);

        $validated = $request->validate([
            'question'  => 'sometimes|string|max:500',
            'answer'    => 'sometimes|string',
            'category'  => 'nullable|string|max:100',
            'priority'  => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq->update($validated);
        $this->autoReplyService->clearCache();

        return response()->json(['success' => true, 'data' => $faq->fresh()]);
    }

    /**
     * DELETE /api/v5/chatbot/admin/faqs/{id}
     */
    public function faqDestroy(int $id): JsonResponse
    {
        ChatbotFaq::findOrFail($id)->delete();
        $this->autoReplyService->clearCache();

        return response()->json(['success' => true, 'message' => 'FAQ deleted.']);
    }

    /* ══════════════════════════════════════════════
     * Keyword Management
     * ══════════════════════════════════════════════ */

    public function keywordIndex(Request $request): JsonResponse
    {
        $keywords = ChatbotKeyword::orderByDesc('priority')
            ->paginate($request->query('per_page', 20));

        return response()->json(['success' => true, 'data' => $keywords]);
    }

    public function keywordStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword'   => 'required|string|max:100|unique:ai_mysql.chatbot_keywords,keyword',
            'response'  => 'required|string',
            'category'  => 'nullable|string|max:100',
            'priority'  => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $keyword = ChatbotKeyword::create($validated);
        $this->autoReplyService->clearCache();

        return response()->json(['success' => true, 'data' => $keyword], 201);
    }

    public function keywordUpdate(int $id, Request $request): JsonResponse
    {
        $keyword = ChatbotKeyword::findOrFail($id);

        $validated = $request->validate([
            'keyword'   => "sometimes|string|max:100|unique:ai_mysql.chatbot_keywords,keyword,{$id}",
            'response'  => 'sometimes|string',
            'category'  => 'nullable|string|max:100',
            'priority'  => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $keyword->update($validated);
        $this->autoReplyService->clearCache();

        return response()->json(['success' => true, 'data' => $keyword->fresh()]);
    }

    public function keywordDestroy(int $id): JsonResponse
    {
        ChatbotKeyword::findOrFail($id)->delete();
        $this->autoReplyService->clearCache();

        return response()->json(['success' => true, 'message' => 'Keyword deleted.']);
    }

    /* ══════════════════════════════════════════════
     * Intent Management
     * ══════════════════════════════════════════════ */

    public function intentIndex(Request $request): JsonResponse
    {
        $intents = ChatbotIntent::with(['keywords', 'responses'])
            ->paginate($request->query('per_page', 20));

        return response()->json(['success' => true, 'data' => $intents]);
    }

    public function intentStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:100|unique:ai_mysql.chatbot_intents,name',
            'description'          => 'nullable|string|max:500',
            'confidence_threshold' => 'nullable|numeric|min:0|max:1',
            'is_active'            => 'nullable|boolean',
            'keywords'             => 'nullable|array',
            'keywords.*.keyword'   => 'required_with:keywords|string|max:100',
            'keywords.*.weight'    => 'nullable|numeric|min:0',
            'responses'            => 'nullable|array',
            'responses.*.response' => 'required_with:responses|string',
            'responses.*.priority' => 'nullable|integer|min:0',
        ]);

        $intent = ChatbotIntent::create([
            'name'                 => $validated['name'],
            'description'          => $validated['description'] ?? null,
            'confidence_threshold' => $validated['confidence_threshold'] ?? 0.6,
            'is_active'            => $validated['is_active'] ?? true,
        ]);

        // Create keywords
        if (! empty($validated['keywords'])) {
            foreach ($validated['keywords'] as $kw) {
                ChatbotIntentKeyword::create([
                    'intent_id' => $intent->id,
                    'keyword'   => $kw['keyword'],
                    'weight'    => $kw['weight'] ?? 1.0,
                ]);
            }
        }

        // Create responses
        if (! empty($validated['responses'])) {
            foreach ($validated['responses'] as $resp) {
                ChatbotIntentResponse::create([
                    'intent_id' => $intent->id,
                    'response'  => $resp['response'],
                    'priority'  => $resp['priority'] ?? 0,
                    'is_active' => true,
                ]);
            }
        }

        $this->autoReplyService->clearCache();

        return response()->json([
            'success' => true,
            'data'    => $intent->load(['keywords', 'responses']),
        ], 201);
    }

    public function intentUpdate(int $id, Request $request): JsonResponse
    {
        $intent = ChatbotIntent::findOrFail($id);

        $validated = $request->validate([
            'name'                 => "sometimes|string|max:100|unique:ai_mysql.chatbot_intents,name,{$id}",
            'description'          => 'nullable|string|max:500',
            'confidence_threshold' => 'nullable|numeric|min:0|max:1',
            'is_active'            => 'nullable|boolean',
            'keywords'             => 'nullable|array',
            'keywords.*.keyword'   => 'required_with:keywords|string|max:100',
            'keywords.*.weight'    => 'nullable|numeric|min:0',
            'responses'            => 'nullable|array',
            'responses.*.response' => 'required_with:responses|string',
            'responses.*.priority' => 'nullable|integer|min:0',
        ]);

        $intent->update(array_filter([
            'name'                 => $validated['name'] ?? null,
            'description'          => $validated['description'] ?? null,
            'confidence_threshold' => $validated['confidence_threshold'] ?? null,
            'is_active'            => $validated['is_active'] ?? null,
        ], fn ($v) => $v !== null));

        // Replace keywords if provided
        if (isset($validated['keywords'])) {
            $intent->keywords()->delete();
            foreach ($validated['keywords'] as $kw) {
                ChatbotIntentKeyword::create([
                    'intent_id' => $intent->id,
                    'keyword'   => $kw['keyword'],
                    'weight'    => $kw['weight'] ?? 1.0,
                ]);
            }
        }

        // Replace responses if provided
        if (isset($validated['responses'])) {
            $intent->responses()->delete();
            foreach ($validated['responses'] as $resp) {
                ChatbotIntentResponse::create([
                    'intent_id' => $intent->id,
                    'response'  => $resp['response'],
                    'priority'  => $resp['priority'] ?? 0,
                    'is_active' => true,
                ]);
            }
        }

        $this->autoReplyService->clearCache();

        return response()->json([
            'success' => true,
            'data'    => $intent->fresh()->load(['keywords', 'responses']),
        ]);
    }

    public function intentDestroy(int $id): JsonResponse
    {
        ChatbotIntent::findOrFail($id)->delete();
        $this->autoReplyService->clearCache();

        return response()->json(['success' => true, 'message' => 'Intent deleted.']);
    }

    /* ══════════════════════════════════════════════
     * Bot Settings
     * ══════════════════════════════════════════════ */

    public function settingsIndex(): JsonResponse
    {
        $settings = ChatbotBotSetting::all(['id', 'key', 'value', 'type', 'description']);

        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function settingsUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings'              => 'required|array',
            'settings.*.key'        => 'required|string|max:100',
            'settings.*.value'      => 'nullable|string',
            'settings.*.type'       => 'nullable|in:string,boolean,integer,json',
            'settings.*.description' => 'nullable|string|max:500',
        ]);

        foreach ($validated['settings'] as $setting) {
            ChatbotBotSetting::setValue(
                $setting['key'],
                $setting['value'],
                $setting['type'] ?? 'string',
                $setting['description'] ?? null,
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated.',
            'data'    => ChatbotBotSetting::all(['id', 'key', 'value', 'type', 'description']),
        ]);
    }

    /* ══════════════════════════════════════════════
     * User Management
     * ══════════════════════════════════════════════ */

    public function userIndex(Request $request): JsonResponse
    {
        $query = ChatbotUser::withCount('conversations');

        if ($platform = $request->query('platform')) {
            $query->where('platform', $platform);
        }
        if ($request->query('blocked') === 'true') {
            $query->blocked();
        }
        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('platform_user_id', 'like', "%{$search}%"));
        }

        $users = $query->orderByDesc('last_seen_at')
            ->paginate($request->query('per_page', 20));

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function blockUser(int $id, Request $request): JsonResponse
    {
        $user = ChatbotUser::findOrFail($id);
        $reason = $request->input('reason', 'Blocked by admin');
        $user->block($reason);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} blocked.",
            'data'    => $user->fresh(),
        ]);
    }

    public function unblockUser(int $id): JsonResponse
    {
        $user = ChatbotUser::findOrFail($id);
        $user->unblock();

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} unblocked.",
            'data'    => $user->fresh(),
        ]);
    }

    /* ══════════════════════════════════════════════
     * Leads
     * ══════════════════════════════════════════════ */

    public function leads(Request $request): JsonResponse
    {
        $query = ChatbotLead::with('chatbotUser:id,name,platform,platform_user_id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }

        $leads = $query->orderByDesc('created_at')
            ->paginate($request->query('per_page', 20));

        return response()->json(['success' => true, 'data' => $leads]);
    }

    public function updateLeadStatus(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,converted,lost',
        ]);

        $lead = ChatbotLead::findOrFail($id);
        $lead->update(['status' => $validated['status']]);

        return response()->json(['success' => true, 'data' => $lead->fresh()]);
    }
}
