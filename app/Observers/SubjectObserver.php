<?php

namespace App\Observers;

use App\Models\Subject;
use App\Support\CacheVersion;

class SubjectObserver
{
    public function saved(Subject $subject): void
    {
        CacheVersion::bump('subjects');
    }

    public function deleted(Subject $subject): void
    {
        CacheVersion::bump('subjects');
    }

    public function restored(Subject $subject): void
    {
        CacheVersion::bump('subjects');
    }

    public function forceDeleted(Subject $subject): void
    {
        CacheVersion::bump('subjects');
    }
}
