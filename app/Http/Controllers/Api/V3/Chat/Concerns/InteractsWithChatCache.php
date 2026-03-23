<?php

namespace App\Http\Controllers\Api\V3\Chat\Concerns;

use App\Models\Chat\ChatConversationParticipant;
use Illuminate\Support\Facades\Cache;

trait InteractsWithChatCache
{
    protected function chatCacheTtlSeconds(): int
    {
        return max(15, (int) config('cache.chat_api_ttl_seconds', 45));
    }

    protected function chatUserVersion(int $userId): int
    {
        return $this->readChatVersion("chat:v3:user:{$userId}:version");
    }

    protected function chatConversationVersion(int $conversationId): int
    {
        return $this->readChatVersion("chat:v3:conversation:{$conversationId}:version");
    }

    protected function bumpChatUserVersion(int $userId): void
    {
        $this->bumpChatVersion("chat:v3:user:{$userId}:version");
    }

    protected function bumpChatConversationVersion(int $conversationId): void
    {
        $this->bumpChatVersion("chat:v3:conversation:{$conversationId}:version");
    }

    /**
     * @param  array<int, int|null>  $extraUserIds
     */
    protected function flushConversationReadCaches(int $conversationId, array $extraUserIds = []): void
    {
        $participantUserIds = ChatConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $this->bumpChatConversationVersion($conversationId);

        $userIds = array_unique(array_filter(array_merge($participantUserIds, $extraUserIds), static fn ($id) => (int) $id > 0));
        foreach ($userIds as $userId) {
            $this->bumpChatUserVersion((int) $userId);
        }
    }

    private function readChatVersion(string $key): int
    {
        $version = Cache::get($key);
        if (! is_int($version) || $version < 1) {
            Cache::forever($key, 1);

            return 1;
        }

        return $version;
    }

    private function bumpChatVersion(string $key): void
    {
        if (! Cache::has($key)) {
            Cache::forever($key, 1);
        }

        Cache::increment($key);
    }
}
