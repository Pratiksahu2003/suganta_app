<?php

namespace App\Services\Chatbot;

use App\Jobs\SendChatbotReplyJob;
use App\Models\Chatbot\ChatbotAnalytics;
use App\Models\Chatbot\ChatbotBotSetting;
use App\Models\Chatbot\ChatbotConversation;
use App\Models\Chatbot\ChatbotMessage;
use App\Models\Chatbot\ChatbotMessageLog;
use App\Models\Chatbot\ChatbotUser;
use App\Models\Chatbot\ChatbotWebhookEvent;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected AutoReplyService    $autoReply;
    protected MetaGraphApiService $metaApi;
    protected LeadCaptureService  $leadCapture;

    public function __construct(
        AutoReplyService    $autoReply,
        MetaGraphApiService $metaApi,
        LeadCaptureService  $leadCapture,
    ) {
        $this->autoReply   = $autoReply;
        $this->metaApi     = $metaApi;
        $this->leadCapture = $leadCapture;
    }

    /* ══════════════════════════════════════════════
     * Main Entry: handle an incoming message
     * ══════════════════════════════════════════════ */

    /**
     * Process a single incoming message from Meta webhook.
     */
    public function handleIncomingMessage(
        string $platform,
        string $senderId,
        string $messageText,
        array  $rawPayload = [],
        string $messageType = 'text',
    ): void {
        $startTime = microtime(true);

        try {
            // 1. Resolve or create chatbot user
            $chatbotUser = $this->resolveOrCreateUser($platform, $senderId);

            // 2. Check if user is blocked → still send a polite message
            if ($chatbotUser->isBlocked()) {
                $this->logEvent($chatbotUser->id, null, $platform, 'message_received', $rawPayload, 'skipped');
                $this->sendEmergencyReply($platform, $senderId, "We're unable to process your request at this time. Please contact support@suganta.co for assistance.");
                return;
            }

            // 3. Touch last seen
            $chatbotUser->touchLastSeen();

            // 4. Resolve or create conversation
            $conversation = $this->resolveOrCreateConversation($chatbotUser, $platform);

            // 5. If conversation was human-controlled but admin is inactive for 30+ min,
            //    auto-release back to bot so the user isn't left waiting
            if ($conversation->isHumanControlled()) {
                $lastAdminReply = ChatbotMessage::where('conversation_id', $conversation->id)
                    ->where('direction', 'outgoing')
                    ->where('matched_by', 'manual')
                    ->latest('created_at')
                    ->first();

                $minutesSinceAdmin = $lastAdminReply
                    ? $lastAdminReply->created_at->diffInMinutes(now())
                    : 999;

                if ($minutesSinceAdmin >= 30) {
                    $conversation->releaseToBot();
                    Log::info('Chatbot: Auto-released stale human conversation back to bot', [
                        'conversation_id' => $conversation->id,
                        'minutes_idle'    => $minutesSinceAdmin,
                    ]);
                }
            }

            // 6. Store the incoming message
            $incomingMessage = $this->storeMessage($conversation, $chatbotUser, 'incoming', $messageText, $messageType, $rawPayload);

            // 7. Increment conversation message count
            $conversation->incrementMessageCount();

            // 8. Log the incoming event
            $this->logEvent($chatbotUser->id, $conversation->id, $platform, 'message_received', $rawPayload, 'success');

            // 9. Update analytics - message received
            ChatbotAnalytics::incrementToday('total_messages_received', $platform);

            // 10. Check for welcome message (first message in conversation)
            if ($conversation->message_count <= 1) {
                $this->handleFirstMessage($platform, $senderId, $chatbotUser, $conversation);
                ChatbotAnalytics::incrementToday('new_users', $platform);
            }

            // 11. ALWAYS get auto-reply (bot replies to every message automatically)
            $replyResult = $this->autoReply->getReply($messageText, $platform);

            // 12. Calculate response time
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // 13. Store outgoing message
            $outgoingMessage = ChatbotMessage::create([
                'conversation_id'  => $conversation->id,
                'chatbot_user_id'  => null,
                'direction'        => 'outgoing',
                'message_type'     => 'text',
                'content'          => $replyResult['reply'],
                'matched_by'       => $replyResult['matched_by'],
                'matched_faq_id'   => $replyResult['faq_id'],
                'matched_intent_id' => $replyResult['intent_id'],
                'delivery_status'  => 'pending',
                'response_time_ms' => $responseTimeMs,
            ]);

            // 14. Dispatch queued job to send via Meta API
            SendChatbotReplyJob::dispatch(
                $platform,
                $senderId,
                $outgoingMessage->id,
            );

            // 15. Update analytics based on match type
            $this->updateMatchAnalytics($replyResult['matched_by'], $platform);
            ChatbotAnalytics::incrementToday('total_messages_sent', $platform);

            // 16. Capture lead if applicable
            $this->leadCapture->captureIfRelevant(
                $chatbotUser,
                $conversation,
                $replyResult['matched_by'],
                $replyResult['intent_id'],
                $messageText,
            );

            // 17. Increment conversation count again for outgoing
            $conversation->incrementMessageCount();

        } catch (\Exception $e) {
            Log::error('Chatbot: Error processing incoming message', [
                'platform'  => $platform,
                'sender_id' => $senderId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            $this->logEvent(
                null, null, $platform, 'error',
                ['message' => $messageText, 'error' => $e->getMessage()],
                'failed',
                $e->getMessage()
            );

            // ALWAYS reply, even on errors — user should never be left in silence
            $this->sendEmergencyReply(
                $platform,
                $senderId,
                config('chatbot.fallback_message', 'Thanks for reaching out to SuGanta! 🎓 Our team will get back to you shortly. Visit suganta.in for more info.')
            );
        }
    }

    /* ══════════════════════════════════════════════
     * Handle webhook delivery / read events
     * ══════════════════════════════════════════════ */

    public function handleDeliveryEvent(string $platform, array $delivery): void
    {
        $mids = $delivery['mids'] ?? [];
        foreach ($mids as $mid) {
            ChatbotMessage::where('meta_message_id', $mid)->update([
                'delivery_status' => 'delivered',
            ]);
        }
    }

    public function handleReadEvent(string $platform, array $read): void
    {
        $watermark = $read['watermark'] ?? null;
        if ($watermark) {
            // Mark all messages before watermark as read
            ChatbotMessage::where('direction', 'outgoing')
                ->where('delivery_status', 'delivered')
                ->where('created_at', '<=', date('Y-m-d H:i:s', $watermark / 1000))
                ->update(['delivery_status' => 'read']);
        }
    }

    /* ══════════════════════════════════════════════
     * Admin: Manual Reply
     * ══════════════════════════════════════════════ */

    public function sendManualReply(int $conversationId, string $text, int $adminId): ChatbotMessage
    {
        $conversation = ChatbotConversation::findOrFail($conversationId);

        $message = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'direction'        => 'outgoing',
            'message_type'     => 'text',
            'content'          => $text,
            'matched_by'       => 'manual',
            'delivery_status'  => 'pending',
        ]);

        $recipientId = $conversation->chatbotUser->platform_user_id;
        $platform    = $conversation->platform;

        SendChatbotReplyJob::dispatch($platform, $recipientId, $message->id);

        $conversation->incrementMessageCount();

        return $message;
    }

    /* ══════════════════════════════════════════════
     * Internals
     * ══════════════════════════════════════════════ */

    protected function resolveOrCreateUser(string $platform, string $senderId): ChatbotUser
    {
        $user = ChatbotUser::where('platform_user_id', $senderId)
            ->where('platform', $platform)
            ->first();

        if ($user) {
            return $user;
        }

        // Fetch profile from Meta
        $profile = $this->metaApi->getUserProfile($platform, $senderId);

        return ChatbotUser::create([
            'platform_user_id' => $senderId,
            'platform'         => $platform,
            'name'             => $profile['name'],
            'profile_pic_url'  => $profile['profile_pic'],
            'locale'           => $profile['locale'],
            'first_seen_at'    => now(),
            'last_seen_at'     => now(),
        ]);
    }

    protected function resolveOrCreateConversation(ChatbotUser $user, string $platform): ChatbotConversation
    {
        // Find the most recent active conversation
        $conversation = ChatbotConversation::where('chatbot_user_id', $user->id)
            ->where('platform', $platform)
            ->active()
            ->orderByDesc('last_message_at')
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return ChatbotConversation::create([
            'chatbot_user_id'  => $user->id,
            'platform'         => $platform,
            'status'           => 'bot',
            'message_count'    => 0,
            'last_message_at'  => now(),
        ]);
    }

    protected function storeMessage(
        ChatbotConversation $conversation,
        ChatbotUser         $user,
        string              $direction,
        string              $content,
        string              $messageType = 'text',
        array               $rawPayload = [],
    ): ChatbotMessage {
        return ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'chatbot_user_id' => $direction === 'incoming' ? $user->id : null,
            'direction'       => $direction,
            'message_type'    => $messageType,
            'content'         => $content,
            'raw_payload'     => ! empty($rawPayload) ? $rawPayload : null,
            'delivery_status' => $direction === 'incoming' ? 'delivered' : 'pending',
        ]);
    }

    protected function handleFirstMessage(
        string              $platform,
        string              $senderId,
        ChatbotUser         $user,
        ChatbotConversation $conversation,
    ): void {
        $welcome = ChatbotBotSetting::getValue('welcome_message')
            ?? config('chatbot.welcome_message');

        if ($welcome) {
            $welcomeMsg = ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'direction'       => 'outgoing',
                'message_type'    => 'text',
                'content'         => $welcome,
                'matched_by'      => 'keyword',
                'delivery_status' => 'pending',
            ]);

            SendChatbotReplyJob::dispatch($platform, $senderId, $welcomeMsg->id);
        }
    }

    protected function updateMatchAnalytics(string $matchedBy, string $platform): void
    {
        $columnMap = [
            'keyword'   => 'keyword_matches',
            'faq'       => 'faq_matches',
            'intent'    => 'intent_matches',
            'ai_gemini' => 'ai_fallbacks',
            'ai_grok'   => 'ai_fallbacks',
            'fallback'  => 'no_matches',
        ];

        $column = $columnMap[$matchedBy] ?? 'no_matches';
        ChatbotAnalytics::incrementToday($column, $platform);
    }

    protected function logEvent(
        ?int    $userId,
        ?int    $conversationId,
        string  $platform,
        string  $eventType,
        array   $payload,
        string  $status = 'success',
        ?string $errorMessage = null,
    ): void {
        try {
            ChatbotMessageLog::create([
                'chatbot_user_id'   => $userId,
                'conversation_id'   => $conversationId,
                'platform'          => $platform,
                'event_type'        => $eventType,
                'payload'           => $payload,
                'processing_status' => $status,
                'error_message'     => $errorMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('Chatbot: Failed to log event', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Emergency reply: sends a message directly (not queued) to guarantee delivery.
     * Used when the normal pipeline fails so the user is NEVER left without a response.
     */
    protected function sendEmergencyReply(string $platform, string $recipientId, string $text): void
    {
        try {
            $this->metaApi->sendTextMessage($platform, $recipientId, $text);
        } catch (\Exception $e) {
            Log::critical('Chatbot: Emergency reply also failed', [
                'platform'     => $platform,
                'recipient_id' => $recipientId,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log raw webhook events for debugging.
     */
    public function logWebhookEvent(string $platform, string $eventType, array $rawPayload): ChatbotWebhookEvent
    {
        return ChatbotWebhookEvent::create([
            'platform'          => $platform,
            'event_type'        => $eventType,
            'raw_payload'       => $rawPayload,
            'processing_status' => 'pending',
        ]);
    }
}
