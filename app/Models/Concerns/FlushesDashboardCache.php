<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

trait FlushesDashboardCache
{
    protected static function flushDashboardCacheForUser(?int $userId): void
    {
        if ($userId) {
            Cache::forget('dashboard:v1:user:'.$userId);
        }
    }
}
