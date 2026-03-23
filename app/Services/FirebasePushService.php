<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebasePushService
{
    private const MAX_TOKENS_PER_USER = 20;
    private const LOG_CHANNEL = 'firebase_push';

    public function registerToken(User $user, string $token, ?string $platform = null, ?string $deviceName = null): array
    {
        $subscription = $this->normalizeSubscription($user->push_subscription ?? []);
        $tokens = $subscription['firebase']['tokens'];
        $beforeCount = count($tokens);

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
        $this->logInfo('push.token.registered', [
            'user_id' => $user->id,
            'platform' => $platform ?? 'unknown',
            'device_name' => $deviceName,
            'token_hash' => $this->tokenHash($token),
            'tokens_before' => $beforeCount,
            'tokens_after' => count($tokens),
        ]);

        return $subscription;
    }

    public function removeToken(User $user, string $token): array
    {
        $subscription = $this->normalizeSubscription($user->push_subscription ?? []);
        $beforeCount = count($subscription['firebase']['tokens']);
        $subscription['firebase']['tokens'] = array_values(array_filter(
            $subscription['firebase']['tokens'],
            static fn (array $item): bool => ($item['token'] ?? null) !== $token
        ));

        $user->push_subscription = $subscription;
        $user->save();
        $this->logInfo('push.token.removed', [
            'user_id' => $user->id,
            'token_hash' => $this->tokenHash($token),
            'tokens_before' => $beforeCount,
            'tokens_after' => count($subscription['firebase']['tokens']),
        ]);

        return $subscription;
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $this->extractTokens($user);
        if (count($tokens) === 0) {
            $this->logInfo('push.send.skipped.no_tokens', [
                'user_id' => $user->id,
                'title' => $title,
                'kind' => $data['kind'] ?? null,
            ]);
            return;
        }

        if (! $this->isConfigured()) {
            $this->logWarning('push.send.skipped.not_configured', [
                'user_id' => $user->id,
                'title' => $title,
                'credentials' => $this->credentialsPath(),
                'kind' => $data['kind'] ?? null,
            ]);
            return;
        }

        try {
            $this->logInfo('push.send.started', [
                'user_id' => $user->id,
                'title' => $title,
                'kind' => $data['kind'] ?? null,
                'token_count' => count($tokens),
                'token_hashes' => array_map(fn (string $t): string => $this->tokenHash($t), $tokens),
            ]);

            $messaging = $this->messaging();
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($this->normalizeData($data));

            $report = $messaging->sendMulticast($message, $tokens);
            $successes = method_exists($report, 'successes') ? count($report->successes()->getItems()) : null;
            $failures = method_exists($report, 'failures') ? count($report->failures()->getItems()) : null;
            $invalidTokens = method_exists($report, 'invalidTokens') ? $report->invalidTokens() : [];
            $unknownTokens = method_exists($report, 'unknownTokens') ? $report->unknownTokens() : [];

            $this->logInfo('push.send.completed', [
                'user_id' => $user->id,
                'title' => $title,
                'kind' => $data['kind'] ?? null,
                'token_count' => count($tokens),
                'success_count' => $successes,
                'failure_count' => $failures,
                'invalid_token_hashes' => array_map(fn (string $t): string => $this->tokenHash($t), is_array($invalidTokens) ? $invalidTokens : []),
                'unknown_token_hashes' => array_map(fn (string $t): string => $this->tokenHash($t), is_array($unknownTokens) ? $unknownTokens : []),
            ]);

            $staleTokens = array_values(array_unique(array_merge(
                is_array($invalidTokens) ? $invalidTokens : [],
                is_array($unknownTokens) ? $unknownTokens : []
            )));
            if ($staleTokens !== []) {
                $this->removeManyTokens($user, $staleTokens);
            }
        } catch (Throwable $e) {
            $this->logWarning('push.send.failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'title' => $title,
                'kind' => $data['kind'] ?? null,
                'credentials' => $this->credentialsPath(),
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
        $credentials = $this->resolvedCredentialsPath();
        if ($credentials === null || trim($credentials) === '') {
            return false;
        }

        if (Str::startsWith(trim($credentials), '{')) {
            return true;
        }

        return file_exists($credentials);
    }

    private function messaging()
    {
        $credentials = $this->resolvedCredentialsPath();
        $factory = (new Factory())
            ->withServiceAccount($credentials);

        $projectId = config('services.firebase.project_id');
        if (is_string($projectId) && $projectId !== '') {
            $factory = $factory->withProjectId($projectId);
        }

        return $factory->createMessaging();
    }

    private function credentialsPath(): ?string
    {
        $credentials = config('services.firebase.credentials');
        return is_string($credentials) ? $credentials : null;
    }

    private function resolvedCredentialsPath(): ?string
    {
        $credentials = $this->credentialsPath();
        if ($credentials === null || trim($credentials) === '') {
            return null;
        }

        $credentials = trim($credentials);

        // Support inline JSON credentials as well.
        if (Str::startsWith($credentials, '{')) {
            return $credentials;
        }

        // Absolute path (Windows or Unix)
        if (preg_match('/^[A-Za-z]:\\\\/', $credentials) === 1 || Str::startsWith($credentials, ['/','\\'])) {
            return $credentials;
        }

        // Relative path from Laravel base path (e.g. storage/keys/file.json)
        return base_path($credentials);
    }

    private function removeManyTokens(User $user, array $tokens): void
    {
        $tokens = array_values(array_filter(array_unique($tokens), fn ($token): bool => is_string($token) && $token !== ''));
        if ($tokens === []) {
            return;
        }

        $subscription = $this->normalizeSubscription($user->push_subscription ?? []);
        $beforeCount = count($subscription['firebase']['tokens']);
        $lookup = array_fill_keys($tokens, true);
        $subscription['firebase']['tokens'] = array_values(array_filter(
            $subscription['firebase']['tokens'],
            static fn (array $item): bool => ! isset($lookup[$item['token'] ?? ''])
        ));

        $user->push_subscription = $subscription;
        $user->save();

        $this->logInfo('push.token.auto_removed_stale', [
            'user_id' => $user->id,
            'removed_count' => count($tokens),
            'tokens_before' => $beforeCount,
            'tokens_after' => count($subscription['firebase']['tokens']),
            'removed_token_hashes' => array_map(fn (string $t): string => $this->tokenHash($t), $tokens),
        ]);
    }

    private function tokenHash(string $token): string
    {
        return substr(hash('sha256', $token), 0, 16);
    }

    private function logInfo(string $event, array $context = []): void
    {
        Log::channel(self::LOG_CHANNEL)->info($event, $context);
    }

    private function logWarning(string $event, array $context = []): void
    {
        Log::channel(self::LOG_CHANNEL)->warning($event, $context);
    }
}
