<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegistrationPaymentService
{
    public function __construct(
        protected CashfreeService $cashfree
    ) {}

    /**
     * Get or create a registration payment checkout URL for the given user.
     *
     * @return array{success: bool, checkout_url?: string, already_paid?: bool, message?: string, order_id?: string, actual_price?: float, discounted_price?: float, description?: string}
     */
    public function getOrCreateCheckoutUrl(User $user, string $source = 'web'): array
    {
        // Already completed — no action needed
        if (in_array($user->registration_fee_status, ['paid', 'not_required'], true)) {
            return ['success' => true, 'already_paid' => true];
        }

        // Normalise null/empty status for legacy users (not_required was already caught above)
        if (empty($user->registration_fee_status)) {
            $user->update(['registration_fee_status' => 'pending']);
        }

        if (!$user->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'Please verify your email address before making payment.',
            ];
        }

        $registrationConfig = config('registration', []);
        $charges            = data_get($registrationConfig, 'charges', []);
        $roleCharges        = is_array($charges) ? ($charges[$user->role] ?? null) : null;

        if (!$roleCharges) {
            $this->logError('Registration charges not found for role', [
                'user_id' => $user->id,
                'role'    => $user->role,
            ]);

            return [
                'success' => false,
                'message' => 'Registration charges not configured for your role. Please contact support.',
            ];
        }

        $requiredRoles  = data_get($registrationConfig, 'payment.required_for_roles', []);
        $requiresPayment = is_array($requiredRoles) && in_array($user->role, $requiredRoles, true);

        if (!$requiresPayment) {
            $user->update([
                'verification_status'     => 'verified',
                'is_active'               => true,
                'registration_fee_status' => 'not_required',
            ]);

            return ['success' => true, 'already_paid' => true];
        }

        if (empty(config('cashfree.app_id')) || empty(config('cashfree.secret_key'))) {
            $this->logError('Registration payment initialization failed — Cashfree not configured', [
                'user_id' => $user->id,
                'role'    => $user->role,
            ]);

            return [
                'success' => false,
                'message' => 'Payment system is not configured. Please contact the administrator.',
            ];
        }

        // Reuse an existing pending / created payment if possible
        $existingPayment = Payment::where('user_id', $user->id)
            ->where('meta->type', 'registration')
            ->whereIn('status', ['created', 'pending'])
            ->latest()
            ->first();

        if ($existingPayment) {
            // Try to extract URL from locally stored gateway response
            $checkoutUrl = $this->cashfree->getCheckoutUrl($existingPayment->gateway_response ?? []);

            if ($checkoutUrl) {
                return [
                    'success'          => true,
                    'checkout_url'     => $checkoutUrl,
                    'order_id'         => $existingPayment->order_id,
                    'actual_price'     => $roleCharges['actual_price'],
                    'discounted_price' => $roleCharges['discounted_price'],
                    'description'      => $roleCharges['description'],
                ];
            }

            // Locally stored response is stale — refresh from Cashfree
            try {
                $freshOrder  = $this->cashfree->getOrder($existingPayment->order_id);
                $checkoutUrl = $this->cashfree->getCheckoutUrl($freshOrder);

                $existingPayment->update(['gateway_response' => $freshOrder]);

                if ($checkoutUrl) {
                    return [
                        'success'          => true,
                        'checkout_url'     => $checkoutUrl,
                        'order_id'         => $existingPayment->order_id,
                        'actual_price'     => $roleCharges['actual_price'],
                        'discounted_price' => $roleCharges['discounted_price'],
                        'description'      => $roleCharges['description'],
                    ];
                }
            } catch (\Exception $e) {
                $this->logError('Failed to refresh existing registration payment order', [
                    'payment_id' => $existingPayment->id,
                    'order_id'   => $existingPayment->order_id,
                    'error'      => $e->getMessage(),
                ]);

                // Mark old record as failed so a new order is created below
                $existingPayment->update(['status' => 'failed']);
            }
        }

        // Create a fresh Cashfree order
        $amount  = $roleCharges['discounted_price'];
        $orderId = 'REG_' . Str::upper(Str::random(10));

        $payment = Payment::create([
            'order_id'  => $orderId,
            'user_id'   => $user->id,
            'currency'  => $roleCharges['currency'] ?? 'INR',
            'amount'    => $amount,
            'status'    => 'created',
            'meta'      => [
                'type'             => 'registration',
                'role'             => $user->role,
                'actual_price'     => $roleCharges['actual_price'],
                'discounted_price' => $roleCharges['discounted_price'],
                'description'      => $roleCharges['description'],
                'source'           => $source,
            ],
        ]);

        try {
            $orderPayload  = $this->cashfree->buildOrderPayload(
                $orderId,
                (string) $user->id,
                $user->email,
                $user->phone ?? '9999999999',
                (float) $amount,
                $roleCharges['currency'] ?? 'INR'
            );

            $orderResponse = $this->cashfree->createOrder($orderPayload);
            $checkoutUrl   = $this->cashfree->getCheckoutUrl($orderResponse);

            $payment->update([
                'reference_id'     => $orderResponse['cf_order_id'] ?? $orderId,
                'gateway_response' => $orderResponse,
                'status'           => 'pending',
            ]);

            $this->logInfo('Registration payment initiated', [
                'order_id'   => $orderId,
                'payment_id' => $payment->id,
                'user_id'    => $user->id,
                'role'       => $user->role,
                'amount'     => $amount,
                'source'     => $source,
            ]);

            return [
                'success'          => true,
                'checkout_url'     => $checkoutUrl,
                'order_id'         => $orderId,
                'actual_price'     => $roleCharges['actual_price'],
                'discounted_price' => $roleCharges['discounted_price'],
                'description'      => $roleCharges['description'],
            ];
        } catch (\Exception $e) {
            $this->logError('Registration payment initiation failed', [
                'order_id'    => $orderId,
                'payment_id'  => $payment->id,
                'user_id'     => $user->id,
                'error'       => $e->getMessage(),
            ]);

            $payment->update([
                'status'           => 'failed',
                'gateway_response' => ['error' => $e->getMessage()],
            ]);

            return [
                'success' => false,
                'message' => 'Payment initialization failed. Please try again or contact support.',
            ];
        }
    }

    /**
     * Handle a successful payment webhook / callback for any order.
     * Updates the payment record and — for registration payments — activates the user.
     */
    public function handlePaymentSuccess(string $orderId, array $paymentData = []): bool
    {
        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            $this->logError('handlePaymentSuccess: payment record not found', ['order_id' => $orderId]);
            return false;
        }

        // Idempotent — already processed
        if ($payment->status === 'success') {
            return true;
        }

        $payment->update([
            'status'           => 'success',
            'processed_at'     => now(),
            'reference_id'     => $paymentData['cf_payment_id'] ?? $payment->reference_id,
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                ['payment_data' => $paymentData]
            ),
        ]);

        // Activate user for registration payments
        if (($payment->meta['type'] ?? '') === 'registration') {
            $user = $payment->user;

            if ($user) {
                $user->update([
                    'registration_fee_status' => 'paid',
                    'verification_status'     => 'verified',
                    'is_active'               => true,
                ]);

                $this->logInfo('Registration payment completed — user activated', [
                    'order_id' => $orderId,
                    'user_id'  => $user->id,
                    'role'     => $user->role,
                ]);
            }
        }

        return true;
    }

    /**
     * Handle a failed or dropped payment for any order.
     */
    public function handlePaymentFailure(string $orderId, array $paymentData = []): bool
    {
        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            $this->logError('handlePaymentFailure: payment record not found', ['order_id' => $orderId]);
            return false;
        }

        if (in_array($payment->status, ['success', 'failed'], true)) {
            return true;
        }

        $payment->update([
            'status'           => 'failed',
            'processed_at'     => now(),
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                ['payment_data' => $paymentData]
            ),
        ]);

        if (($payment->meta['type'] ?? '') === 'registration') {
            $user = $payment->user;

            if ($user) {
                $user->update(['registration_fee_status' => 'failed']);
            }

            $this->logError('Registration payment failed', [
                'order_id' => $orderId,
                'user_id'  => $user?->id ?? null,
            ]);
        }

        return true;
    }

    private function logInfo(string $message, array $context = []): void
    {
        try {
            Log::channel('payment')->info($message, $context);
        } catch (\Exception $e) {
            Log::info($message, $context);
        }
    }

    private function logError(string $message, array $context = []): void
    {
        try {
            Log::channel('payment')->error($message, $context);
        } catch (\Exception $e) {
            Log::error($message, $context);
        }
    }
}
