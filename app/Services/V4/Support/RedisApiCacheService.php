<?php

namespace App\Services\V4\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class RedisApiCacheService
{
    public function remember(string $key, int $ttlSeconds, Closure $callback): mixed
    {
        return Cache::store($this->resolveStore())->remember(
            $this->prefixKey($key),
            max(10, $ttlSeconds),
            $callback
        );
    }

    public function forget(string $key): bool
    {
        return Cache::store($this->resolveStore())->forget($this->prefixKey($key));
    }

    public function readVersion(string $key): int
    {
        $versionKey = $this->prefixKey('version:'.$key);
        $version = Cache::store($this->resolveStore())->get($versionKey);
        if (! is_int($version) || $version < 1) {
            Cache::store($this->resolveStore())->forever($versionKey, 1);

            return 1;
        }

        return $version;
    }

    public function bumpVersion(string $key): int
    {
        $versionKey = $this->prefixKey('version:'.$key);
        if (! Cache::store($this->resolveStore())->has($versionKey)) {
            Cache::store($this->resolveStore())->forever($versionKey, 1);
        }

        return (int) Cache::store($this->resolveStore())->increment($versionKey);
    }

    private function resolveStore(): string
    {
        return config('cache.v4_google_cache_store', 'redis');
    }

    private function prefixKey(string $key): string
    {
        return 'api:v4:'.$key;
    }
}
