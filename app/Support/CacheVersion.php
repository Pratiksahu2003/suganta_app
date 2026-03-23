<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheVersion
{
    public static function get(string $namespace, int $default = 1): int
    {
        $key = self::key($namespace);
        $current = Cache::get($key);

        if ($current === null) {
            Cache::forever($key, $default);
            return $default;
        }

        return (int) $current;
    }

    public static function bump(string $namespace): int
    {
        $key = self::key($namespace);
        if (!Cache::has($key)) {
            Cache::forever($key, 1);
        }

        return (int) Cache::increment($key);
    }

    private static function key(string $namespace): string
    {
        return "cache_version:{$namespace}";
    }
}
