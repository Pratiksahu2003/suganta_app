<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebasePushService
{
    private const MAX_TOKENS_PER_USER = 20;

    public function registerToken(User $user, string $token, ?string $platform = null, ?string $deviceName = null): array
    {
        $subscription = $this->normalizeSubscription($user->push_subscription ?? []);
        $tokens = $subscription['firebase']['tokens'];

        $tokens = array_values(array_filter($tokens, static fn (array $item): bool => ($item['token'] ?? null) !== $token));
        $tokens[] = [
            'token' => $token,
            'platform' => $platform ?? 'unknown',
            'device_name' => $deviceName,
            'updated_at' => now()->toIso8601String(),
        ];

        if (count($tokens) > self::MAX_TOKENS_PER_USER) {
            $tokens = array_slice($tokens, -self::MAX_TOKENS_PER_USER);
        }

        $subscription['firebase']['tokens'] = $tokens;
        $user->push_subscription = $subscription;
        $user->save();

        return $subscription;
    }

    public function removeToken(User $user, string $token): array
    {
        $subscription = $this->normalizeSubscription($user->push_subscription ?? []);
        $subscription['firebase']['tokens'] = array_values(array_filter(
            $subscription['firebase']['tokens'],
            static fn (array $item): bool => ($item['token'] ?? null) !== $token
        ));

        $user->push_subscription = $subscription;
        $user->save();

        return $subscription;
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $this->extractTokens($user);
        if (count($tokens) === 0 || ! $this->isConfigured()) {
            return;
        }

        try {
            $messaging = $this->messaging();
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($this->normalizeData($data));

            $messaging->sendMulticast($message, $tokens);
        } catch (Throwable $e) {
            Log::warning('Firebase push send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function extractTokens(User $user): array
    {
        $subscription = $this->normalizeSubscription($user->push_subscription ?? []);

        $tokens = [];
        foreach ($subscription['firebase']['tokens'] as $item) {
            $token = is_array($item) ? ($item['token'] ?? null) : null;
            if (is_string($token) && $token !== '') {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    private function normalizeSubscription(mixed $subscription): array
    {
        $result = is_array($subscription) ? $subscription : [];
        $result['firebase'] = Arr::get($result, 'firebase', []);
        $result['firebase']['tokens'] = array_values(array_filter(
            Arr::get($result, 'firebase.tokens', []),
            static fn ($item): bool => is_array($item)
        ));

        return $result;
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[(string) $key] = is_scalar($value) || $value === null
                ? (string) ($value ?? '')
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $normalized;
    }

    private function isConfigured(): bool
    {
        return (bool) config('services.firebase.project_id') && (bool) config('services.firebase.credentials');
    }

    private function messaging()
    {
        return (new Factory())
            ->withServiceAccount(config('services.firebase.credentials'))
            ->withProjectId(config('services.firebase.project_id'))
            ->createMessaging();
    }
}
