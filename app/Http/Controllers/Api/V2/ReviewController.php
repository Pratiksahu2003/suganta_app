<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\StoreReviewRequest;
use App\Http\Requests\Api\V2\UpdateReviewRequest;
use App\Http\Resources\V2\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReviewService $reviewService,
    ) {}

    /**
     * GET /v2/reviews
     * List all reviews for a user. Latest first, 10 per page by default, paginated.
     *
     * Query params: reviewable_type=user, reviewable_id=user_id, rating, verified, has_comment, search, sort, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => ['required', 'string', 'in:user'],
            'reviewable_id' => ['required', 'integer', 'min:1'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'verified' => ['nullable', 'boolean'],
            'has_comment' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:latest,oldest,highest,lowest,helpful'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], [
            'reviewable_type.required' => 'Please specify the user (reviewable_type and reviewable_id).',
            'reviewable_id.required' => 'Please specify the user ID you want to view reviews for.',
        ]);

        $filters = $request->only(['rating', 'verified', 'has_comment', 'search', 'sort', 'per_page']);
        $filters['per_page'] = $filters['per_page'] ?? 10;

        $reviews = $this->reviewService->getReviewsForEntity(
            $request->input('reviewable_type'),
            (int) $request->input('reviewable_id'),
            $filters,
        );

        return $this->paginated(
            ReviewResource::collection($reviews),
            'User reviews fetched successfully.',
        );
    }

    /**
     * POST /v2/reviews
     * Submit a new review.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $review = $this->reviewService->createReview(
            $request->user(),
            $request->validated(),
        );

        return $this->created(
            new ReviewResource($review),
            'Thank you! Your review has been submitted successfully.',
        );
    }

    /**
     * GET /v2/reviews/{review}
     * Show a single review.
     */
    public function show(Review $review): JsonResponse
    {
        $review->load(['user', 'reviewable']);

        return $this->success(
            'Review details fetched successfully.',
            new ReviewResource($review),
        );
    }

    /**
     * PUT/PATCH /v2/reviews/{review}
     * Update own review.
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $review = $this->reviewService->updateReview(
            $review,
            $request->user(),
            $request->validated(),
        );

        return $this->success(
            'Your review has been updated successfully.',
            new ReviewResource($review),
        );
    }

    /**
     * DELETE /v2/reviews/{review}
     * Delete own review.
     */
    public function destroy(Request $request, Review $review): JsonResponse
    {
        $this->reviewService->deleteReview($review, $request->user());

        return $this->success('Your review has been deleted successfully.');
    }

    /**
     * GET /v2/reviews/my
     * Get authenticated user's reviews.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string', 'in:published,pending,rejected,hidden'],
            'sort' => ['nullable', 'string', 'in:latest,oldest,highest,lowest'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $reviews = $this->reviewService->getUserReviews(
            $request->user(),
            $request->only(['status', 'sort', 'per_page']),
        );

        return $this->paginated(
            ReviewResource::collection($reviews),
            'Your reviews fetched successfully.',
        );
    }

    /**
     * GET /v2/reviews/stats
     * Get rating stats & distribution for a user.
     *
     * Query params: reviewable_type=user, reviewable_id=user_id
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => ['required', 'string', 'in:user'],
            'reviewable_id' => ['required', 'integer', 'min:1'],
        ]);

        $stats = $this->reviewService->getEntityStats(
            $request->input('reviewable_type'),
            (int) $request->input('reviewable_id'),
        );

        return $this->success('Rating statistics fetched successfully.', $stats);
    }

    /**
     * POST /v2/reviews/{review}/helpful
     * Mark a review as helpful (increment count).
     */
    public function markHelpful(Request $request, Review $review): JsonResponse
    {
        $review = $this->reviewService->markHelpful($review, $request->user());

        return $this->success('Thanks! You marked this review as helpful.', [
            'helpful_count' => $review->helpful_count,
        ]);
    }

    /**
     * POST /v2/reviews/{review}/reply
     * The reviewed user can reply to the review.
     */
    public function reply(Request $request, Review $review): JsonResponse
    {
        $request->validate([
            'reply' => ['required', 'string', 'max:3000'],
        ], [
            'reply.required' => 'Please enter your reply.',
            'reply.max' => 'Reply cannot exceed 3000 characters.',
        ]);

        $user = $request->user();
        $reviewable = $review->reviewable;

        $canReply = $reviewable && $review->reviewable_id === $user->id;

        if (!$canReply) {
            return $this->forbidden('Only the reviewed user can reply to this review.');
        }

        $review = $this->reviewService->replyToReview($review, $request->input('reply'));

        return $this->success(
            'Your reply has been added successfully.',
            new ReviewResource($review),
        );
    }

    /**
     * POST /v2/reviews/{review}/report
     * Report a review for moderation.
     */
    public function report(Request $request, Review $review): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Please provide a reason for reporting.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
        ]);

        if ($review->user_id === $request->user()->id) {
            return $this->error('You cannot report your own review.', 422);
        }

        $report = $this->reviewService->reportReview(
            $review,
            $request->user(),
            $request->input('reason'),
        );

        return $this->success('Thank you. Your report has been submitted. Our team will review it shortly.', $report);
    }

    /**
     * GET /v2/reviews/check
     * Check if the current user can review a user (and if they already have).
     *
     * Query params: reviewable_type=user, reviewable_id=user_id
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => ['required', 'string', 'in:user'],
            'reviewable_id' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $reviewable = $this->reviewService->resolveReviewable(
            $request->input('reviewable_type'),
            (int) $request->input('reviewable_id'),
        );

        $existingReview = Review::where('user_id', $user->id)
            ->where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->first();

        $canReview = !$existingReview && $user->canReview($reviewable);

        $data = [
            'can_review' => $canReview,
            'has_reviewed' => (bool) $existingReview,
            'existing_review' => $existingReview ? new ReviewResource($existingReview->load('user')) : null,
        ];

        return $this->success('Review eligibility checked successfully.', $data);
    }
}
