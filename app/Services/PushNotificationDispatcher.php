<?php

namespace App\Services;

use App\Models\Chat\ChatConversationParticipant;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

        $decision = $this->pushDecision($notification, $title, $body, $data);
        if (! $decision['send']) {
            Log::channel('firebase_push')->info('push.dispatch.notification.skipped.smart_filter', [
                'notification_id' => (string) $notification->id,
                'notifiable_id' => $notification->notifiable_id,
                'reason' => $decision['reason'],
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

    /**
     * @return array{send: bool, reason: string}
     */
    private function pushDecision(Notification $notification, string $title, string $body, array $data): array
    {
        if ($this->isAuthTokenRelatedNotification($title, $body, $data)) {
            return ['send' => false, 'reason' => 'auth_or_token_related'];
        }

        if (! (bool) config('push.notifications.smart_filter.enabled', true)) {
            return ['send' => true, 'reason' => 'smart_filter_disabled'];
        }

        $type = strtolower((string) ($data['type'] ?? 'general'));
        $priority = strtolower((string) ($data['priority'] ?? 'normal'));

        $alwaysAllowTypes = config('push.notifications.smart_filter.always_allow_types', []);
        if (is_array($alwaysAllowTypes) && in_array($type, array_map('strtolower', $alwaysAllowTypes), true)) {
            if ($this->isDuplicateWithinWindow($notification, $title, $body, $type, $data)) {
                return ['send' => false, 'reason' => 'duplicate_within_window'];
            }

            return ['send' => true, 'reason' => 'always_allow_type'];
        }

        if ($this->isImportantModelActivity($data)) {
            if ($this->isDuplicateWithinWindow($notification, $title, $body, $type, $data)) {
                return ['send' => false, 'reason' => 'duplicate_within_window'];
            }

            return ['send' => true, 'reason' => 'important_model_activity'];
        }

        $minPriority = strtolower((string) config('push.notifications.smart_filter.min_priority', 'high'));
        if ($this->priorityRank($priority) < $this->priorityRank($minPriority)) {
            return ['send' => false, 'reason' => 'below_min_priority'];
        }

        if ($this->isDuplicateWithinWindow($notification, $title, $body, $type, $data)) {
            return ['send' => false, 'reason' => 'duplicate_within_window'];
        }

        return ['send' => true, 'reason' => 'priority_threshold_met'];
    }

    private function isImportantModelActivity(array $data): bool
    {
        if (! isset($data['event'], $data['model'])) {
            return false;
        }

        $modelPushModels = config('push.notifications.smart_filter.model_push_models', []);
        if (! is_array($modelPushModels) || $modelPushModels === []) {
            return false;
        }

        return in_array((string) $data['model'], $modelPushModels, true);
    }

    private function priorityRank(string $priority): int
    {
        return match (strtolower($priority)) {
            'urgent' => 3,
            'high' => 2,
            'normal' => 1,
            default => 0,
        };
    }

    private function isAuthTokenRelatedNotification(string $title, string $body, array $data): bool
    {
        $haystack = implode(' ', [
            $title,
            $body,
            json_encode($data, JSON_UNESCAPED_UNICODE) ?: '',
        ]);

        foreach ($this->authTokenPatterns() as $pattern) {
            if (preg_match($pattern, $haystack) === 1) {
                return true;
            }
        }

        return false;
    }

    private function authTokenPatterns(): array
    {
        return [
            '/\bauth(?:entication)?\s*token\b/i',
            '/\b(access|refresh|bearer|remember|reset)\s*token\b/i',
            '/\bapi\s*access\s*token\b/i',
            '/\bpersonal\s*access\s*token\b/i',
            '/\bsanctum\b/i',
            '/\bpassword\s*reset\b/i',
            '/\breset\s*password\b/i',
            '/\bforgot\s*password\b/i',
            '/\b(email\s*)?verification\s*code\b/i',
            '/\bverify\s*email\b/i',
            '/\bone\s*time\s*password\b/i',
            '/\botp\b/i',
            '/\btwo\s*factor\b/i',
            '/\b2fa\b/i',
            '/\blog(in|out)\b/i',
            '/\bsign\s*(in|out)\b/i',
        ];
    }

    private function isDuplicateWithinWindow(
        Notification $notification,
        string $title,
        string $body,
        string $type,
        array $data
    ): bool {
        $seconds = max(0, (int) config('push.notifications.smart_filter.dedupe_seconds', 120));
        if ($seconds === 0) {
            return false;
        }

        $fingerprint = implode('|', [
            (string) $notification->notifiable_id,
            $type,
            strtolower(trim($title)),
            strtolower(trim($body)),
            strtolower((string) ($data['event'] ?? '')),
            strtolower((string) ($data['model'] ?? '')),
            (string) ($data['model_id'] ?? ''),
        ]);

        $key = 'push:dedupe:' . sha1($fingerprint);

        // add returns false when key already exists in window.
        return ! Cache::add($key, 1, $seconds);
    }
}
