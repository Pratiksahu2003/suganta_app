<?php

namespace App\Observers;

use App\Models\Review;
use App\Support\CacheVersion;

class ReviewObserver
{
    public function saved(Review $review): void
    {
        CacheVersion::bump('reviews_public');
    }

    public function deleted(Review $review): void
    {
        CacheVersion::bump('reviews_public');
    }

    public function restored(Review $review): void
    {
        CacheVersion::bump('reviews_public');
    }

    public function forceDeleted(Review $review): void
    {
        CacheVersion::bump('reviews_public');
    }
}
