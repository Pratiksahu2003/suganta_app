<?php

namespace App\Services;

use App\Models\Chat\ChatConversationParticipant;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PushNotificationDispatcher
{
    public function __construct(private readonly FirebasePushService $firebasePushService)
    {
    }

    public function dispatchForNotification(Notification $notification): void
    {
        if (! $this->isPushEnabled() || ! $this->isNotificationPushEnabled()) {
            return;
        }

        if ($notification->notifiable_type !== User::class) {
            return;
        }

        $user = User::query()->find($notification->notifiable_id);
        if (! $user) {
            Log::channel('firebase_push')->warning('push.dispatch.notification.skipped.user_not_found', [
                'notification_id' => (string) $notification->id,
                'notifiable_id' => $notification->notifiable_id,
            ]);
            return;
        }

        $data = is_array($notification->data) ? $notification->data : [];
        $title = (string) ($data['title'] ?? 'New Notification');
        $body = (string) ($data['message'] ?? 'You have a new notification.');

        if ($this->isAuthTokenRelatedNotification($title, $body, $data)) {
            Log::channel('firebase_push')->info('push.dispatch.notification.skipped.auth_token_related', [
                'notification_id' => (string) $notification->id,
                'notifiable_id' => $notification->notifiable_id,
            ]);
            return;
        }

        $this->firebasePushService->sendToUser($user, $title, $body, [
            'kind' => 'system_notification',
            'notification_id' => (string) $notification->id,
            'type' => (string) ($data['type'] ?? 'general'),
            'priority' => (string) ($data['priority'] ?? 'normal'),
            'action_url' => isset($data['action_url']) && is_scalar($data['action_url']) ? (string) $data['action_url'] : null,
        ]);
    }

    public function dispatchChatEvent(
        int $conversationId,
        int $actorUserId,
        string $title,
        string $body,
        array $payload,
        string $eventKey
    ): void {
        if (! $this->isPushEnabled() || ! $this->isChatPushEnabled() || ! $this->isChatEventEnabled($eventKey)) {
            return;
        }

        $recipientIds = ChatConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->whereNull('left_at')
            ->where('user_id', '!=', $actorUserId)
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        $recipients = User::query()->whereIn('id', $recipientIds->all())->get(['id', 'push_subscription']);
        foreach ($recipients as $recipient) {
            $this->firebasePushService->sendToUser($recipient, $title, $body, $payload);
        }
    }

    private function isPushEnabled(): bool
    {
        return (bool) config('push.enabled', true);
    }

    private function isNotificationPushEnabled(): bool
    {
        return (bool) config('push.notifications.enabled', true);
    }

    private function isChatPushEnabled(): bool
    {
        return (bool) config('push.chat.enabled', true);
    }

    private function isChatEventEnabled(string $eventKey): bool
    {
        return (bool) config("push.chat.events.{$eventKey}", true);
    }

    private function isAuthTokenRelatedNotification(string $title, string $body, array $data): bool
    {
        $haystack = strtolower(implode(' ', [
            $title,
            $body,
            json_encode($data, JSON_UNESCAPED_UNICODE) ?: '',
        ]));

        foreach ($this->authTokenKeywords() as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function authTokenKeywords(): array
    {
        return [
            'auth token',
            'authentication token',
            'access token',
            'refresh token',
            'bearer token',
            'remember token',
            'reset token',
            'password reset token',
            'api token',
        ];
    }
}
