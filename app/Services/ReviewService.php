<?php

namespace App\Services;

use App\Exceptions\DuplicateReviewException;
use App\Exceptions\ReviewNotAllowedException;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReviewService
{
    protected array $reviewableMap = [
        'user' => User::class,
    ];

    public function resolveReviewable(string $type, int $id): Model
    {
        $modelClass = $this->reviewableMap[$type] ?? null;

        if (!$modelClass) {
            throw new InvalidArgumentException("Invalid reviewable type: {$type}");
        }

        return $modelClass::findOrFail($id);
    }

    public function createReview(User $user, array $data): Review
    {
        $reviewable = $this->resolveReviewable($data['reviewable_type'], $data['reviewable_id']);

        if ($this->hasExistingReview($user, $reviewable)) {
            throw new DuplicateReviewException(
                'You have already submitted a review for this user. You can edit your existing review instead.'
            );
        }

        if (!$this->canUserReview($user, $reviewable)) {
            throw new ReviewNotAllowedException(
                'You cannot review yourself.'
            );
        }

        return DB::transaction(function () use ($user, $reviewable, $data) {
            $review = new Review();
            $review->user_id = $user->id;
            $review->reviewable_type = get_class($reviewable);
            $review->reviewable_id = $reviewable->id;
            $review->rating = $data['rating'];
            $review->title = $data['title'] ?? null;
            $review->comment = $data['comment'] ?? null;
            $review->tags = $data['tags'] ?? null;
            $review->status = 'published';
            $review->reviewed_at = now();
            $review->save();

            $review->load(['user', 'reviewable']);

            return $review;
        });
    }

    public function updateReview(Review $review, User $user, array $data): Review
    {
        $this->authorizeOwnership($review, $user);

        return DB::transaction(function () use ($review, $data) {
            if (isset($data['rating'])) {
                $review->rating = $data['rating'];
            }
            if (array_key_exists('title', $data)) {
                $review->title = $data['title'];
            }
            if (array_key_exists('comment', $data)) {
                $review->comment = $data['comment'];
            }
            if (array_key_exists('tags', $data)) {
                $review->tags = $data['tags'];
            }

            $review->save();
            $review->load(['user', 'reviewable']);

            return $review;
        });
    }

    public function deleteReview(Review $review, User $user): void
    {
        $this->authorizeOwnership($review, $user);

        $review->delete();
    }

    public function getReviewsForEntity(string $type, int $id, array $filters = []): LengthAwarePaginator
    {
        $reviewable = $this->resolveReviewable($type, $id);

        $query = Review::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->published()
            ->with(['user']);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters['sort'] ?? 'latest');

        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function getUserReviews(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Review::where('user_id', $user->id)
            ->with(['user', 'reviewable']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $this->applySorting($query, $filters['sort'] ?? 'latest');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getEntityStats(string $type, int $id): array
    {
        $reviewable = $this->resolveReviewable($type, $id);
        $morphClass = get_class($reviewable);

        $stats = Review::where('reviewable_type', $morphClass)
            ->where('reviewable_id', $reviewable->id)
            ->published()
            ->selectRaw('
                COUNT(*) as total_reviews,
                COALESCE(AVG(rating), 0) as average_rating,
                COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_count,
                SUM(helpful_count) as total_helpful
            ')
            ->first();

        $distribution = Review::where('reviewable_type', $morphClass)
            ->where('reviewable_id', $reviewable->id)
            ->published()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->pluck('count', 'rating')
            ->toArray();

        $fullDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $distribution[$i] ?? 0;
            $percentage = $stats->total_reviews > 0
                ? round(($count / $stats->total_reviews) * 100, 1)
                : 0;
            $fullDistribution[] = [
                'rating' => $i,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return [
            'total_reviews' => (int) $stats->total_reviews,
            'average_rating' => round((float) $stats->average_rating, 1),
            'verified_count' => (int) $stats->verified_count,
            'total_helpful' => (int) ($stats->total_helpful ?? 0),
            'distribution' => $fullDistribution,
        ];
    }

    public function markHelpful(Review $review, User $user): Review
    {
        if ($review->user_id === $user->id) {
            throw new ReviewNotAllowedException(
                'You cannot mark your own review as helpful.'
            );
        }

        $review->increment('helpful_count');
        $review->refresh();

        return $review;
    }

    public function replyToReview(Review $review, string $replyText): Review
    {
        $review->reply = $replyText;
        $review->replied_at = now();
        $review->save();
        $review->load(['user', 'reviewable']);

        return $review;
    }

    public function reportReview(Review $review, User $user, string $reason): array
    {
        return [
            'review_id' => $review->id,
            'reported_by' => $user->id,
            'reason' => $reason,
            'reported_at' => now()->toISOString(),
        ];
    }

    protected function hasExistingReview(User $user, Model $reviewable): bool
    {
        return Review::where('user_id', $user->id)
            ->where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->exists();
    }

    protected function canUserReview(User $user, Model $reviewable): bool
    {
        // When reviewing a user directly, prevent self-review
        if ($reviewable instanceof User && $user->id === $reviewable->id) {
            return false;
        }

        return true;
    }

    protected function authorizeOwnership(Review $review, User $user): void
    {
        if ($review->user_id !== $user->id) {
            throw new AuthorizationException(
                'You can only edit or delete your own reviews.'
            );
        }
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['rating'])) {
            $query->where('rating', (int) $filters['rating']);
        }

        if (isset($filters['verified']) && $filters['verified']) {
            $query->verified();
        }

        if (isset($filters['has_comment']) && $filters['has_comment']) {
            $query->withComments();
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%");
            });
        }
    }

    protected function applySorting($query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'highest' => $query->orderBy('rating', 'desc')->latest(),
            'lowest' => $query->orderBy('rating', 'asc')->latest(),
            'helpful' => $query->orderBy('helpful_count', 'desc')->latest(),
            default => $query->latest(),
        };
    }
}
