<?php

namespace App\Observers;

use App\Models\Profile;
use App\Support\CacheVersion;

class ProfileObserver
{
    public function saved(Profile $profile): void
    {
        $profile->clearCompletionCache();
        CacheVersion::bump('teachers_public');
        CacheVersion::bump('institutes_public');
    }

    public function deleted(Profile $profile): void
    {
        CacheVersion::bump('teachers_public');
        CacheVersion::bump('institutes_public');
    }

    public function restored(Profile $profile): void
    {
        CacheVersion::bump('teachers_public');
        CacheVersion::bump('institutes_public');
    }

    public function forceDeleted(Profile $profile): void
    {
        CacheVersion::bump('teachers_public');
        CacheVersion::bump('institutes_public');
    }
}
