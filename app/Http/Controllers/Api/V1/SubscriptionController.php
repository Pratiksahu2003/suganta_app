<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\PurchaseSubscriptionRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\UserSubscriptionResource;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends BaseApiController
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Get all active subscription plans
     */
    public function plans(Request $request): JsonResponse
    {
        $sType = $request->query('s_type', 1);
        
        $plans = SubscriptionPlan::active()
            ->where('s_type', $sType)
            ->orderBy('sort_order', 'asc')
            ->orderBy('price', 'asc')
            ->get();

        return $this->success('Subscription plans retrieved successfully.', [
            'plans' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    /**
     * Get a specific subscription plan
     */
    public function plan(SubscriptionPlan $plan): JsonResponse
    {
        if (!$plan->is_active) {
            return $this->notFound('Subscription plan not found or inactive.');
        }

        return $this->success('Subscription plan retrieved successfully.', [
            'plan' => new SubscriptionPlanResource($plan),
        ]);
    }

    /**
     * Get current user's subscriptions
     */
    public function mySubscriptions(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = UserSubscription::where('user_id', $user->id)
            ->with(['plan', 'payment'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Filter by subscription type if provided
        if ($request->filled('s_type')) {
            $query->whereHas('plan', function ($q) use ($request) {
                $q->where('s_type', $request->integer('s_type'));
            });
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $subscriptions = $query->paginate($perPage);

        return $this->success('User subscriptions retrieved successfully.', [
            'data' => UserSubscriptionResource::collection($subscriptions->items()),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
                'from' => $subscriptions->firstItem(),
                'to' => $subscriptions->lastItem(),
            ],
            'links' => [
                'first' => $subscriptions->url(1),
                'last' => $subscriptions->url($subscriptions->lastPage()),
                'prev' => $subscriptions->previousPageUrl(),
                'next' => $subscriptions->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get current user's active subscription for a specific type
     */
    public function currentSubscription(Request $request): JsonResponse
    {
        $user = Auth::user();
        $sType = $request->query('s_type', 1);

        $subscription = UserSubscription::where('user_id', $user->id)
            ->whereHas('plan', function ($q) use ($sType) {
                $q->where('s_type', $sType);
            })
            ->active()
            ->with(['plan', 'payment'])
            ->first();

        if (!$subscription) {
            return $this->success('No active subscription found.', [
                'subscription' => null,
            ]);
        }

        return $this->success('Current subscription retrieved successfully.', [
            'subscription' => new UserSubscriptionResource($subscription),
        ]);
    }

    /**
     * Purchase a subscription plan
     */
    public function purchase(PurchaseSubscriptionRequest $request): JsonResponse
    {
        $user = Auth::user();
        $planId = $request->validated('subscription_plan_id');
        
        $plan = SubscriptionPlan::active()->find($planId);
        
        if (!$plan) {
            return $this->notFound('Subscription plan not found or inactive.');
        }

        $result = $this->subscriptionService->getOrCreateSubscriptionCheckoutUrl($user, $plan, 'api');

        if (!$result['success']) {
            return $this->error(
                $result['message'] ?? 'Failed to create subscription payment.',
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($result['already_paid'] ?? false) {
            return $this->success('Subscription payment already completed.', [
                'order_id' => $result['order_id'] ?? null,
                'status' => 'already_paid',
                'subscription_plan' => new SubscriptionPlanResource($plan),
            ]);
        }

        return $this->success('Subscription payment created successfully.', [
            'payment' => [
                'order_id' => $result['order_id'],
                'amount' => $result['amount'],
                'currency' => $result['currency'],
                'status' => 'pending',
            ],
            'checkout_url' => $result['checkout_url'],
            'payment_session_id' => $result['payment_session_id'] ?? null,
            'subscription_plan' => new SubscriptionPlanResource($plan),
        ]);
    }

    /**
     * Cancel a subscription
     */
    public function cancel(UserSubscription $subscription): JsonResponse
    {
        $user = Auth::user();

        if ($subscription->user_id !== $user->id) {
            return $this->forbidden('You can only cancel your own subscriptions.');
        }

        if ($subscription->status !== 'active') {
            return $this->error(
                'Only active subscriptions can be cancelled.',
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->subscriptionService->cancelSubscription($subscription);

            return $this->success('Subscription cancelled successfully.', [
                'subscription' => new UserSubscriptionResource($subscription->fresh()),
            ]);

        } catch (\Exception $e) {
            return $this->error(
                'Failed to cancel subscription: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Renew a subscription
     */
    public function renew(UserSubscription $subscription): JsonResponse
    {
        $user = Auth::user();

        if ($subscription->user_id !== $user->id) {
            return $this->forbidden('You can only renew your own subscriptions.');
        }

        if ($subscription->status === 'active' && $subscription->isActive()) {
            return $this->error(
                'This subscription is still active and does not need renewal.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $plan = $subscription->plan;
        if (!$plan) {
            return $this->error('Subscription plan not found.', Response::HTTP_NOT_FOUND);
        }

        $result = $this->subscriptionService->getOrCreateSubscriptionCheckoutUrl($user, $plan, 'api_renewal');

        if (!$result['success']) {
            return $this->error(
                $result['message'] ?? 'Failed to create renewal payment.',
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($result['already_paid'] ?? false) {
            return $this->success('Subscription renewal payment already completed.', [
                'order_id' => $result['order_id'] ?? null,
                'status' => 'already_paid',
                'subscription' => new UserSubscriptionResource($subscription->fresh()),
            ]);
        }

        // Update the payment meta to link with the renewal subscription
        $payment = Payment::where('order_id', $result['order_id'])->first();
        if ($payment) {
            $payment->update([
                'meta' => array_merge($payment->meta, [
                    'renewal_for_subscription_id' => $subscription->id,
                ])
            ]);
        }

        return $this->success('Subscription renewal payment created successfully.', [
            'payment' => [
                'order_id' => $result['order_id'],
                'amount' => $result['amount'],
                'currency' => $result['currency'],
                'status' => 'pending',
            ],
            'checkout_url' => $result['checkout_url'],
            'payment_session_id' => $result['payment_session_id'] ?? null,
            'subscription' => new UserSubscriptionResource($subscription),
        ]);
    }
}