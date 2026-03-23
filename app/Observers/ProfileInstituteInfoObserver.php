<?php

namespace App\Observers;

use App\Models\ProfileInstituteInfo;
use App\Support\CacheVersion;

class ProfileInstituteInfoObserver
{
    public function saved(ProfileInstituteInfo $instituteInfo): void
    {
        CacheVersion::bump('institutes_public');
    }

    public function deleted(ProfileInstituteInfo $instituteInfo): void
    {
        CacheVersion::bump('institutes_public');
    }
}
