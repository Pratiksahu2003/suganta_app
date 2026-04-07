<?php

namespace App\Services\V4\Google;

use App\Models\GoogleWatchChannel;
use App\Models\User;
use App\Services\V4\Support\RedisApiCacheService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleWatchService
{
    public function __construct(
        private readonly GoogleApiService $googleApiService,
        private readonly RedisApiCacheService $cacheService
    ) {}

    public function startWatch(User $user, string $accessToken, string $resourceType, int $ttlSeconds = 3600): GoogleWatchChannel
    {
        $webhookUrl = (string) config('services.google.webhook_url');
        $webhookSecret = (string) config('services.google.webhook_secret');
        if ($webhookUrl === '' || $webhookSecret === '') {
            throw new RuntimeException('Google webhook URL/secret is not configured on server.', 500);
        }

        $channelId = (string) Str::uuid();
        $token = $this->buildSignedToken($channelId, (int) $user->id, $resourceType, $webhookSecret);
        $ttl = max(300, min(604800, $ttlSeconds));

        $response = match ($resourceType) {
            'calendar' => $this->googleApiService->watchCalendarEvents($accessToken, $channelId, $webhookUrl, $token, $ttl),
            'drive' => $this->googleApiService->watchDriveChanges($accessToken, $channelId, $webhookUrl, $token, $ttl),
            default => throw new RuntimeException('Unsupported resource type for watch.', 422),
        };

        return GoogleWatchChannel::create([
            'user_id' => $user->id,
            'resource_type' => $resourceType,
            'channel_id' => $channelId,
            'resource_id' => (string) data_get($response, 'resourceId', ''),
            'google_resource_uri' => data_get($response, 'resourceUri'),
            'verification_token' => $token,
            'status' => 'active',
            'expires_at' => $this->resolveExpiry(data_get($response, 'expiration')),
            'meta' => $response,
        ]);
    }

    public function stopWatch(User $user, string $channelId, string $accessToken): void
    {
        $channel = GoogleWatchChannel::query()
            ->where('user_id', $user->id)
            ->where('channel_id', $channelId)
            ->where('status', 'active')
            ->first();

        if (! $channel) {
            throw new RuntimeException('Active watch channel not found.', 404);
        }

        $this->googleApiService->stopWatchChannel($accessToken, (string) $channel->channel_id, (string) $channel->resource_id, (string) $channel->resource_type);

        $channel->update([
            'status' => 'stopped',
            'meta' => array_merge((array) $channel->meta, ['stopped_at' => now()->toIso8601String()]),
        ]);
    }

    public function renewChannel(GoogleWatchChannel $channel, string $accessToken): GoogleWatchChannel
    {
        if ($channel->status !== 'active') {
            throw new RuntimeException('Only active watch channels can be renewed.', 422);
        }

        $user = User::find($channel->user_id);
        if (!$user) {
            throw new RuntimeException('Channel user not found for renewal.', 404);
        }

        $this->stopWatchSilently($channel, $accessToken);

        return $this->startWatch(
            $user,
            $accessToken,
            (string) $channel->resource_type,
            (int) config('services.google.watch_token_ttl_seconds', 86400)
        );
    }

    public function expiringChannels(?CarbonInterface $cutoff = null)
    {
        $effectiveCutoff = $cutoff ?: now()->addSeconds(
            max(60, (int) config('services.google.watch_renew_before_seconds', 900))
        );

        return GoogleWatchChannel::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $effectiveCutoff)
            ->orderBy('expires_at');
    }

    public function handleWebhook(array $headers): void
    {
        $channelId = (string) ($headers['x-goog-channel-id'] ?? '');
        if ($channelId === '') {
            return;
        }

        $channel = GoogleWatchChannel::query()->where('channel_id', $channelId)->first();
        if (! $channel) {
            return;
        }

        $incomingToken = (string) ($headers['x-goog-channel-token'] ?? '');
        if (! $this->verifyIncomingToken((string) $channel->verification_token, $incomingToken, $channel)) {
            Log::warning('Google webhook token mismatch.', ['channel_id' => $channelId]);
            return;
        }

        $state = (string) ($headers['x-goog-resource-state'] ?? 'unknown');
        $messageNumber = (int) ($headers['x-goog-message-number'] ?? 0);
        if ($this->isReplay($channelId, $messageNumber, $headers)) {
            Log::warning('Google webhook replay rejected.', [
                'channel_id' => $channelId,
                'message_number' => $messageNumber,
            ]);
            return;
        }

        $channel->update([
            'last_message_number' => $messageNumber > 0 ? $messageNumber : $channel->last_message_number,
            'last_notification_at' => now(),
            'status' => $state === 'sync' ? $channel->status : 'active',
            'meta' => array_merge((array) $channel->meta, [
                'last_state' => $state,
                'last_resource_id' => (string) ($headers['x-goog-resource-id'] ?? ''),
            ]),
        ]);

        $this->cacheService->bumpVersion('google:watch:user:'.$channel->user_id.':'.$channel->resource_type);
    }

    private function resolveExpiry(mixed $expiration): ?\Illuminate\Support\Carbon
    {
        if (! is_numeric($expiration)) {
            return null;
        }

        return now()->setTimestamp((int) floor(((int) $expiration) / 1000));
    }

    private function buildSignedToken(string $channelId, int $userId, string $resourceType, string $secret): string
    {
        $issuedAt = now()->timestamp;
        $ttl = max(300, (int) config('services.google.watch_token_ttl_seconds', 86400));
        $payload = [
            'cid' => $channelId,
            'uid' => $userId,
            'rt' => $resourceType,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $ttl,
        ];
        $encoded = base64_encode((string) json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encoded, $secret);

        return $encoded.'.'.$signature;
    }

    private function verifyIncomingToken(string $storedToken, string $incomingToken, GoogleWatchChannel $channel): bool
    {
        if (! hash_equals($storedToken, $incomingToken)) {
            return false;
        }

        $parts = explode('.', $incomingToken, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$encoded, $sig] = $parts;
        $secret = (string) config('services.google.webhook_secret');
        $expected = hash_hmac('sha256', $encoded, $secret);
        if (! hash_equals($expected, $sig)) {
            return false;
        }

        $decoded = json_decode((string) base64_decode($encoded, true), true);
        if (! is_array($decoded)) {
            return false;
        }

        $exp = (int) ($decoded['exp'] ?? 0);
        $cid = (string) ($decoded['cid'] ?? '');
        $uid = (int) ($decoded['uid'] ?? 0);
        $rt = (string) ($decoded['rt'] ?? '');

        return $exp >= now()->timestamp
            && $cid === $channel->channel_id
            && $uid === (int) $channel->user_id
            && $rt === (string) $channel->resource_type;
    }

    private function isReplay(string $channelId, int $messageNumber, array $headers): bool
    {
        $windowSeconds = max(30, (int) config('services.google.webhook_replay_window_seconds', 300));

        if ($messageNumber > 0) {
            $replayKey = 'google:webhook:replay:'.$channelId.':'.$messageNumber;
            if (Cache::has($replayKey)) {
                return true;
            }
            Cache::put($replayKey, now()->timestamp, now()->addSeconds($windowSeconds));
        }

        $channelTime = (string) ($headers['x-goog-channel-expiration'] ?? '');
        if ($channelTime !== '') {
            $parsed = strtotime($channelTime);
            if ($parsed !== false && abs(now()->timestamp - $parsed) > 31536000) {
                return true;
            }
        }

        return false;
    }

    private function stopWatchSilently(GoogleWatchChannel $channel, string $accessToken): void
    {
        try {
            $this->googleApiService->stopWatchChannel($accessToken, (string) $channel->channel_id, (string) $channel->resource_id, (string) $channel->resource_type);
        } catch (\Throwable $exception) {
            // If the channel is already gone (404), it's successfully stopped from Google's perspective.
            if ($exception->getCode() !== 404) {
                Log::warning('Google watch stop during renewal failed.', [
                    'channel_id' => $channel->channel_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        } finally {
            $channel->update([
                'status' => 'stopped',
                'meta' => array_merge((array) $channel->meta, ['stopped_at' => now()->toIso8601String(), 'stopped_by' => 'renewal']),
            ]);
        }
    }
}
