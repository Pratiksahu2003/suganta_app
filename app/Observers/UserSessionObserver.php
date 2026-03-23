<?php

namespace App\Observers;

use App\Models\UserSession;
use App\Support\CacheVersion;

class UserSessionObserver
{
    public function saved(UserSession $userSession): void
    {
        CacheVersion::bump("session_stats_user:{$userSession->user_id}");
    }

    public function deleted(UserSession $userSession): void
    {
        CacheVersion::bump("session_stats_user:{$userSession->user_id}");
    }
}
