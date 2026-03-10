<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function __construct(
        protected CashfreeService $cashfree
    ) {}

    /**
     * Create a subscription payment for the given user and plan.
     *
     * @return array{payment: Payment, checkout_url: string, payment_session_id?: string}
     */
    public function createSubscriptionPayment(User $user, SubscriptionPlan $plan): array
    {
        if (!$user->hasVerifiedEmail()) {
            throw new \Exception('Please verify your email address before purchasing a subscription.');
        }

        if (empty(config('cashfree.app_id')) || empty(config('cashfree.secret_key'))) {
            throw new \Exception('Payment system is not configured. Please contact the administrator.');
        }

        // Check for existing pending payment for this plan
        $existingPayment = Payment::where('user_id', $user->id)
            ->where('meta->type', 'subscription')
            ->where('meta->subscription_plan_id', $plan->id)
            ->whereIn('status', ['created', 'pending'])
            ->latest()
            ->first();

        if ($existingPayment) {
            try {
                $freshOrder = $this->cashfree->getOrder($existingPayment->order_id);
                $orderStatus = strtoupper($freshOrder['order_status'] ?? '');

                if ($this->cashfree->isOrderPaid($freshOrder)) {
                    // Payment was completed, process it
                    $this->processSuccessfulPayment($existingPayment, $freshOrder);
                    throw new \Exception('Payment has already been completed for this subscription.');
                }

                if (in_array($orderStatus, ['ACTIVE'], true)) {
                    // Order still active, return existing checkout URL
                    $existingPayment->update(['gateway_response' => $freshOrder]);
                    $checkoutUrl = $this->cashfree->getCheckoutUrl($freshOrder);

                    if ($checkoutUrl) {
                        return [
                            'payment' => $existingPayment,
                            'checkout_url' => $checkoutUrl,
                            'payment_session_id' => $freshOrder['payment_session_id'] ?? null,
                        ];
                    }
                }

                // Order expired or failed, mark as failed
                $existingPayment->update(['status' => 'failed', 'gateway_response' => $freshOrder]);
            } catch (\Exception $e) {
                Log::error('Failed to refresh existing subscription payment order', [
                    'payment_id' => $existingPayment->id,
                    'order_id' => $existingPayment->order_id,
                    'error' => $e->getMessage(),
                ]);

                $existingPayment->update(['status' => 'failed']);
            }
        }

        // Create new payment order
        $orderId = 'SUB_' . Str::upper(Str::random(10));
        $amount = $plan->price;

        $payment = Payment::create([
            'order_id' => $orderId,
            'user_id' => $user->id,
            'currency' => $plan->currency,
            'amount' => $amount,
            'status' => 'created',
            'meta' => [
                'type' => 'subscription',
                'subscription_plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'billing_period' => $plan->billing_period,
                's_type' => $plan->s_type,
            ],
        ]);

        try {
            $orderPayload = $this->cashfree->buildOrderPayload(
                $orderId,
                (string) $user->id,
                $user->email,
                $user->phone ?? '9999999999',
                (float) $amount,
                $plan->currency
            );

            $orderResponse = $this->cashfree->createOrder($orderPayload);
            $checkoutUrl = $this->cashfree->getCheckoutUrl($orderResponse);

            $payment->update([
                'reference_id' => $orderResponse['cf_order_id'] ?? $orderId,
                'gateway_response' => $orderResponse,
                'status' => 'pending',
            ]);

            Log::info('Subscription payment initiated', [
                'order_id' => $orderId,
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $amount,
            ]);

            return [
                'payment' => $payment,
                'checkout_url' => $checkoutUrl,
                'payment_session_id' => $orderResponse['payment_session_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Subscription payment initiation failed', [
                'order_id' => $orderId,
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            $payment->update(['status' => 'failed']);
            throw new \Exception('Failed to create payment order: ' . $e->getMessage());
        }
    }

    /**
     * Process a successful subscription payment.
     */
    public function processSuccessfulPayment(Payment $payment, array $gatewayResponse = []): UserSubscription
    {
        return DB::transaction(function () use ($payment, $gatewayResponse) {
            // Update payment status
            $payment->update([
                'status' => 'success',
                'gateway_response' => $gatewayResponse,
                'processed_at' => now(),
            ]);

            $planId = $payment->meta['subscription_plan_id'] ?? null;
            if (!$planId) {
                throw new \Exception('Subscription plan ID not found in payment meta data.');
            }

            $plan = SubscriptionPlan::find($planId);
            if (!$plan) {
                throw new \Exception('Subscription plan not found.');
            }

            // Calculate subscription dates
            $startsAt = now();
            $expiresAt = $this->calculateExpirationDate($startsAt, $plan->billing_period);

            // Cancel any existing active subscriptions of the same type
            UserSubscription::where('user_id', $payment->user_id)
                ->whereHas('plan', function ($q) use ($plan) {
                    $q->where('s_type', $plan->s_type);
                })
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            // Create new subscription
            $subscription = UserSubscription::create([
                'user_id' => $payment->user_id,
                'subscription_plan_id' => $plan->id,
                'payment_id' => $payment->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'payment_method' => 'cashfree',
                'transaction_id' => $gatewayResponse['cf_payment_id'] ?? $payment->reference_id,
                'amount_paid' => $payment->amount,
            ]);

            Log::info('Subscription created successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $payment->user_id,
                'plan_id' => $plan->id,
                'payment_id' => $payment->id,
                'expires_at' => $expiresAt->toDateTimeString(),
            ]);

            return $subscription;
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(UserSubscription $subscription): void
    {
        $subscription->update([
            'status' => 'cancelled',
        ]);

        Log::info('Subscription cancelled', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->subscription_plan_id,
        ]);
    }

    /**
     * Renew a subscription by creating a new payment.
     */
    public function renewSubscription(UserSubscription $subscription): array
    {
        $plan = $subscription->plan;
        $user = $subscription->user;

        if (!$plan || !$user) {
            throw new \Exception('Subscription plan or user not found.');
        }

        // Create renewal payment
        $result = $this->createSubscriptionPayment($user, $plan);

        // Update the subscription meta to link with the new payment
        $result['payment']->update([
            'meta' => array_merge($result['payment']->meta, [
                'renewal_for_subscription_id' => $subscription->id,
            ])
        ]);

        return $result;
    }

    /**
     * Calculate expiration date based on billing period.
     */
    protected function calculateExpirationDate(Carbon $startDate, string $billingPeriod): Carbon
    {
        return match (strtolower($billingPeriod)) {
            'daily' => $startDate->copy()->addDay(),
            'weekly' => $startDate->copy()->addWeek(),
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'half_yearly', 'semi_annually' => $startDate->copy()->addMonths(6),
            'yearly', 'annually' => $startDate->copy()->addYear(),
            'lifetime' => null, // No expiration for lifetime plans
            default => $startDate->copy()->addMonth(), // Default to monthly
        };
    }

    /**
     * Get fresh checkout data for an existing payment (used by PaymentController).
     */
    public function getFreshCheckoutData(Payment $payment): ?array
    {
        if ($payment->status === 'success') {
            return ['already_paid' => true];
        }

        if (!in_array($payment->status, ['created', 'pending'], true)) {
            return null;
        }

        try {
            $freshOrder = $this->cashfree->getOrder($payment->order_id);
            $orderStatus = strtoupper($freshOrder['order_status'] ?? '');

            if ($this->cashfree->isOrderPaid($freshOrder)) {
                // Process the payment if it's been paid
                if ($payment->meta['type'] === 'subscription') {
                    $this->processSuccessfulPayment($payment, $freshOrder);
                }
                return ['already_paid' => true];
            }

            if (in_array($orderStatus, ['ACTIVE'], true)) {
                $payment->update(['gateway_response' => $freshOrder]);
                $checkoutUrl = $this->cashfree->getCheckoutUrl($freshOrder);

                if ($checkoutUrl) {
                    return [
                        'checkout_url' => $checkoutUrl,
                        'payment_session_id' => $freshOrder['payment_session_id'] ?? null,
                    ];
                }
            }

            // Order expired or failed
            $payment->update(['status' => 'failed', 'gateway_response' => $freshOrder]);
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get fresh checkout data', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if user has active subscription for a specific type.
     */
    public function hasActiveSubscription(User $user, int $sType): bool
    {
        return UserSubscription::where('user_id', $user->id)
            ->whereHas('plan', function ($q) use ($sType) {
                $q->where('s_type', $sType);
            })
            ->active()
            ->exists();
    }

    /**
     * Get user's active subscription for a specific type.
     */
    public function getActiveSubscription(User $user, int $sType): ?UserSubscription
    {
        return UserSubscription::where('user_id', $user->id)
            ->whereHas('plan', function ($q) use ($sType) {
                $q->where('s_type', $sType);
            })
            ->active()
            ->with(['plan', 'payment'])
            ->first();
    }
}