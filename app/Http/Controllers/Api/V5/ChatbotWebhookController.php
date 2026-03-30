<?php

namespace App\Http\Controllers\Api\V5;

use App\Http\Controllers\Controller;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\MetaGraphApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ChatbotWebhookController extends Controller
{
    public function __construct(
        protected ChatbotService      $chatbotService,
        protected MetaGraphApiService $metaApi,
    ) {}

    /**
     * GET /api/v5/chatbot/webhook
     *
     * Meta webhook verification endpoint.
     * Meta sends a GET request with hub.mode, hub.verify_token, hub.challenge.
     */
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('chatbot.meta_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Chatbot: Webhook verification successful');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Chatbot: Webhook verification failed', [
            'mode'  => $mode,
            'token' => $token,
        ]);

        return response('Verification failed', 403);
    }

    /**
     * POST /api/v5/chatbot/webhook
     *
     * Handle incoming webhook events from Meta (Instagram + Messenger).
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify webhook signature
        $signature = $request->header('X-Hub-Signature-256', '');
        $payload   = $request->getContent();

        if (! empty(config('chatbot.meta_app_secret'))) {
            if (! $this->metaApi->verifyWebhookSignature($payload, $signature)) {
                Log::warning('Chatbot: Invalid webhook signature');
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $body = $request->all();

        // Meta sends 'page' for Messenger, 'instagram' for Instagram
        $object = $body['object'] ?? '';

        if (! in_array($object, ['page', 'instagram'])) {
            Log::info('Chatbot: Ignoring non-messaging webhook', ['object' => $object]);
            return response()->json(['status' => 'ignored'], 200);
        }

        $platform = $object === 'instagram' ? 'instagram' : 'messenger';

        // Process each entry
        $entries = $body['entry'] ?? [];

        foreach ($entries as $entry) {
            // Log the raw webhook event
            $webhookEvent = $this->chatbotService->logWebhookEvent($platform, 'incoming', $entry);

            try {
                $this->processEntry($platform, $entry);
                $webhookEvent->markProcessed();
            } catch (\Exception $e) {
                Log::error('Chatbot: Error processing webhook entry', [
                    'platform' => $platform,
                    'error'    => $e->getMessage(),
                ]);
                $webhookEvent->markFailed($e->getMessage());
            }
        }

        // Always return 200 to Meta (so they don't retry)
        return response()->json(['status' => 'ok'], 200);
    }

    /* ══════════════════════════════════════════════
     * Internal Processing
     * ══════════════════════════════════════════════ */

    protected function processEntry(string $platform, array $entry): void
    {
        // Instagram uses 'messaging', Messenger also uses 'messaging'
        $messagingEvents = $entry['messaging'] ?? [];

        foreach ($messagingEvents as $event) {
            $senderId = $event['sender']['id'] ?? null;

            if (! $senderId) {
                continue;
            }

            // Skip if sender is our page (echo)
            $pageId = $event['recipient']['id'] ?? null;
            if ($senderId === $pageId) {
                continue;
            }

            // Handle different event types
            if (isset($event['message'])) {
                $this->handleMessageEvent($platform, $senderId, $event);
            } elseif (isset($event['delivery'])) {
                $this->chatbotService->handleDeliveryEvent($platform, $event['delivery']);
            } elseif (isset($event['read'])) {
                $this->chatbotService->handleReadEvent($platform, $event['read']);
            } elseif (isset($event['postback'])) {
                $this->handlePostbackEvent($platform, $senderId, $event);
            }
        }
    }

    protected function handleMessageEvent(string $platform, string $senderId, array $event): void
    {
        $message = $event['message'];

        // Skip echo messages (messages sent by the page itself)
        if (! empty($message['is_echo'])) {
            return;
        }

        // Determine message type and content
        $messageText = $message['text'] ?? '';
        $messageType = 'text';

        // Handle quick reply payloads
        if (isset($message['quick_reply'])) {
            $messageText = $message['quick_reply']['payload'] ?? $messageText;
            $messageType = 'quick_reply';
        }

        // Handle attachments (images, etc.)
        if (empty($messageText) && isset($message['attachments'])) {
            $attachment = $message['attachments'][0] ?? null;
            if ($attachment) {
                $messageType = $attachment['type'] ?? 'image';
                $messageText = $attachment['payload']['url'] ?? '[attachment]';
            }
        }

        if (empty($messageText)) {
            return;
        }

        // Process the message through ChatbotService
        $this->chatbotService->handleIncomingMessage(
            $platform,
            $senderId,
            $messageText,
            $event,
            $messageType,
        );
    }

    protected function handlePostbackEvent(string $platform, string $senderId, array $event): void
    {
        $postback  = $event['postback'];
        $payload   = $postback['payload'] ?? '';
        $title     = $postback['title'] ?? $payload;

        if (empty($payload)) {
            return;
        }

        // Treat postback as a regular text message with the payload
        $this->chatbotService->handleIncomingMessage(
            $platform,
            $senderId,
            $payload,
            $event,
            'quick_reply',
        );
    }
}
