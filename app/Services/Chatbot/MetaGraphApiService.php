<?php

namespace App\Services\Chatbot;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaGraphApiService
{
    protected string $apiVersion;
    protected string $messengerToken;
    protected string $instagramToken;
    protected string $appSecret;

    public function __construct()
    {
        $this->apiVersion     = (string) config('chatbot.meta_api_version', 'v19.0');
        $this->messengerToken = (string) config('chatbot.meta_page_token', '');
        $this->instagramToken = (string) config('chatbot.meta_ig_page_token') ?: $this->messengerToken;
        $this->appSecret      = (string) config('chatbot.meta_app_secret', '');
    }

    /* ──────────────────────────────────────────────
     * Sending Messages
     * ────────────────────────────────────────────── */

    /**
     * Send a text message to a user.
     *
     * @return array{message_id: string|null, success: bool, error: string|null}
     */
    public function sendTextMessage(string $platform, string $recipientId, string $text): array
    {
        $payload = [
            'recipient' => ['id' => $recipientId],
            'message'   => ['text' => $this->truncateText($text, 2000)],
        ];

        return $this->sendMessage($platform, $payload);
    }

    /**
     * Send a message with quick reply buttons.
     */
    public function sendQuickReply(string $platform, string $recipientId, string $text, array $buttons): array
    {
        $quickReplies = [];
        foreach (array_slice($buttons, 0, 13) as $button) { // Meta max: 13 quick replies
            $quickReplies[] = [
                'content_type' => 'text',
                'title'        => mb_substr($button['title'] ?? $button, 0, 20),
                'payload'      => $button['payload'] ?? $button['title'] ?? $button,
            ];
        }

        $payload = [
            'recipient' => ['id' => $recipientId],
            'message'   => [
                'text'          => $this->truncateText($text, 2000),
                'quick_replies' => $quickReplies,
            ],
        ];

        return $this->sendMessage($platform, $payload);
    }

    /**
     * Send a generic template message.
     */
    public function sendGenericTemplate(string $platform, string $recipientId, array $elements): array
    {
        $payload = [
            'recipient' => ['id' => $recipientId],
            'message'   => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements'      => array_slice($elements, 0, 10),
                    ],
                ],
            ],
        ];

        return $this->sendMessage($platform, $payload);
    }

    /**
     * Send typing indicator (sender action).
     */
    public function sendTypingIndicator(string $platform, string $recipientId, bool $on = true): void
    {
        $payload = [
            'recipient'     => ['id' => $recipientId],
            'sender_action' => $on ? 'typing_on' : 'typing_off',
        ];

        try {
            $this->sendMessage($platform, $payload);
        } catch (Exception $e) {
            Log::warning('Chatbot: Failed to send typing indicator', ['error' => $e->getMessage()]);
        }
    }

    /* ──────────────────────────────────────────────
     * User Profile
     * ────────────────────────────────────────────── */

    /**
     * Get user profile from Meta Graph API.
     *
     * @return array{name: string|null, profile_pic: string|null, locale: string|null}
     */
    public function getUserProfile(string $platform, string $userId): array
    {
        $token = $this->getToken($platform);
        $fields = $platform === 'instagram'
            ? 'name,profile_pic'
            : 'first_name,last_name,profile_pic,locale';

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$userId}";

        try {
            $response = Http::timeout(5)->get($url, [
                'fields'       => $fields,
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'name'        => $data['name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: null,
                    'profile_pic' => $data['profile_pic'] ?? null,
                    'locale'      => $data['locale'] ?? null,
                ];
            }
        } catch (Exception $e) {
            Log::warning('Chatbot: Failed to fetch user profile', [
                'platform' => $platform,
                'user_id'  => $userId,
                'error'    => $e->getMessage(),
            ]);
        }

        return ['name' => null, 'profile_pic' => null, 'locale' => null];
    }

    /* ──────────────────────────────────────────────
     * Webhook Verification
     * ────────────────────────────────────────────── */

    /**
     * Verify the X-Hub-Signature-256 from Meta webhook.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->appSecret)) {
            Log::error('Chatbot: META_APP_SECRET not configured');
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $this->appSecret);

        return hash_equals($expected, $signature);
    }

    /* ──────────────────────────────────────────────
     * Internal
     * ────────────────────────────────────────────── */

    protected function sendMessage(string $platform, array $payload): array
    {
        $token = $this->getToken($platform);
        $url = "https://graph.facebook.com/{$this->apiVersion}/me/messages";

        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'message_id' => $data['message_id'] ?? null,
                    'success'    => true,
                    'error'      => null,
                ];
            }

            $error = $response->json('error.message', 'Unknown Meta API error');
            Log::error('Chatbot: Meta API send failed', [
                'platform' => $platform,
                'status'   => $response->status(),
                'error'    => $error,
                'body'     => $response->body(),
            ]);

            return [
                'message_id' => null,
                'success'    => false,
                'error'      => $error,
            ];

        } catch (Exception $e) {
            Log::error('Chatbot: Meta API exception', [
                'platform' => $platform,
                'error'    => $e->getMessage(),
            ]);

            return [
                'message_id' => null,
                'success'    => false,
                'error'      => $e->getMessage(),
            ];
        }
    }

    protected function getToken(string $platform): string
    {
        return $platform === 'instagram' ? $this->instagramToken : $this->messengerToken;
    }

    protected function truncateText(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength - 3) . '...';
    }
}
