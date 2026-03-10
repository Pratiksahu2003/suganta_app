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

        // Reuse an existing pending / created payment if it is still active on Cashfree
        $existingPayment = Payment::where('user_id', $user->id)
            ->where('meta->type', 'registration')
            ->whereIn('status', ['created', 'pending'])
            ->latest()
            ->first();

        if ($existingPayment) {
            // Always verify with Cashfree — never trust locally-cached session IDs
            // because they may be stale (e.g. the order was created while the code was
            // pointing at the wrong endpoint, or the session has since expired).
            try {
                $freshOrder  = $this->cashfree->getOrder($existingPayment->order_id);
                $orderStatus = strtoupper($freshOrder['order_status'] ?? '');

                if ($this->cashfree->isOrderPaid($freshOrder)) {
                    // Race condition: paid between two requests — activate user
                    $this->handlePaymentSuccess($existingPayment->order_id, []);
                    return ['success' => true, 'already_paid' => true];
                }

                if (in_array($orderStatus, ['ACTIVE'], true)) {
                    // Order still active — use fresh session from Cashfree
                    $existingPayment->update(['gateway_response' => $freshOrder]);
                    $checkoutUrl = $this->cashfree->getCheckoutUrl($freshOrder);

                    if ($checkoutUrl) {
                        return [
                            'success'             => true,
                            'checkout_url'        => $checkoutUrl,
                            'payment_session_id'  => $freshOrder['payment_session_id'] ?? null,
                            'order_id'            => $existingPayment->order_id,
                            'actual_price'        => $roleCharges['actual_price'],
                            'discounted_price'    => $roleCharges['discounted_price'],
                            'description'         => $roleCharges['description'],
                        ];
                    }
                }

                // Order is EXPIRED, CANCELLED, or no usable session — mark failed and create new
                $existingPayment->update(['status' => 'failed', 'gateway_response' => $freshOrder]);
            } catch (\Exception $e) {
                $this->logError('Failed to refresh existing registration payment order', [
                    'payment_id' => $existingPayment->id,
                    'order_id'   => $existingPayment->order_id,
                    'error'      => $e->getMessage(),
                ]);

                // If Cashfree returns 404 the order never existed; mark as failed
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
                'success'            => true,
                'checkout_url'       => $checkoutUrl,
                'payment_session_id' => $orderResponse['payment_session_id'] ?? null,
                'order_id'           => $orderId,
                'actual_price'       => $roleCharges['actual_price'],
                'discounted_price'   => $roleCharges['discounted_price'],
                'description'        => $roleCharges['description'],
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

    /**
     * Handle refund status updates for any order.
     * Updates the payment record and — for registration payments — updates user status accordingly.
     */
    public function handleRefundStatus(string $orderId, array $paymentData = []): bool
    {
        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            $this->logError('handleRefundStatus: payment record not found', ['order_id' => $orderId]);
            return false;
        }

        // Extract refund status from payment data
        $refundStatus = strtoupper($paymentData['refund_status'] ?? $paymentData['status'] ?? '');
        $refundAmount = $paymentData['refund_amount'] ?? $paymentData['amount'] ?? 0;
        
        // Handle different refund statuses
        switch ($refundStatus) {
            case 'SUCCESS':
            case 'PROCESSED':
            case 'COMPLETED':
                return $this->processRefundSuccess($payment, $paymentData, $refundAmount);
                
            case 'FAILED':
            case 'REJECTED':
                return $this->processRefundFailure($payment, $paymentData);
                
            case 'PENDING':
            case 'PROCESSING':
                return $this->processRefundPending($payment, $paymentData);
                
            default:
                $this->logError('handleRefundStatus: unknown refund status', [
                    'order_id' => $orderId,
                    'refund_status' => $refundStatus,
                    'payment_data' => $paymentData
                ]);
                return false;
        }
    }

    /**
     * Process successful refund
     */
    private function processRefundSuccess(Payment $payment, array $paymentData, float $refundAmount): bool
    {
        // Idempotent — already processed
        if ($payment->status === 'refunded') {
            return true;
        }

        $payment->update([
            'status' => 'refunded',
            'processed_at' => now(),
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                [
                    'refund_data' => $paymentData,
                    'refund_amount' => $refundAmount,
                    'refund_processed_at' => now()->toISOString()
                ]
            ),
        ]);

        // Handle registration payment refund
        if (($payment->meta['type'] ?? '') === 'registration') {
            $user = $payment->user;

            if ($user) {
                // Update user registration fee status
                $user->update([
                    'registration_fee_status' => 'refunded',
                    'is_active' => false, // Deactivate user account
                    'verification_status' => 'pending', // Reset verification status
                ]);

                $this->logInfo('Registration payment refunded — user deactivated', [
                    'order_id' => $payment->order_id,
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'refund_amount' => $refundAmount,
                ]);
            }
        }

        $this->logInfo('Refund processed successfully', [
            'order_id' => $payment->order_id,
            'payment_id' => $payment->id,
            'refund_amount' => $refundAmount,
        ]);

        return true;
    }

    /**
     * Process failed refund
     */
    private function processRefundFailure(Payment $payment, array $paymentData): bool
    {
        $payment->update([
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                [
                    'refund_data' => $paymentData,
                    'refund_failed_at' => now()->toISOString()
                ]
            ),
        ]);

        $this->logError('Refund processing failed', [
            'order_id' => $payment->order_id,
            'payment_id' => $payment->id,
            'refund_data' => $paymentData,
        ]);

        return true;
    }

    /**
     * Process pending refund
     */
    private function processRefundPending(Payment $payment, array $paymentData): bool
    {
        $payment->update([
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                [
                    'refund_data' => $paymentData,
                    'refund_pending_at' => now()->toISOString()
                ]
            ),
        ]);

        $this->logInfo('Refund processing pending', [
            'order_id' => $payment->order_id,
            'payment_id' => $payment->id,
        ]);

        return true;
    }

    /**
     * Get a guaranteed-fresh Cashfree checkout data for an existing Payment record.
     *
     * Called by the proxy checkout endpoint so that any payment link we hand
     * out NEVER goes stale:
     *   1. Verify the Cashfree order is still ACTIVE.
     *   2. If expired / 404 → mark failed, create a brand-new order.
     *   3. Returns ['payment_session_id' => ..., 'checkout_url' => ...], or
     *      ['already_paid' => true] when the payment was completed, or null
     *      on unrecoverable error.
     *
     * @return array{payment_session_id?: string, checkout_url?: string, already_paid?: bool}|null
     */
    public function getFreshCheckoutData(Payment $payment): ?array
    {
        // ── Step 1: verify with Cashfree ──────────────────────────────────────
        try {
            $freshOrder  = $this->cashfree->getOrder($payment->order_id);
            $orderStatus = strtoupper($freshOrder['order_status'] ?? '');

            // Race condition: paid between requests — activate user
            if ($this->cashfree->isOrderPaid($freshOrder)) {
                $this->handlePaymentSuccess($payment->order_id, []);
                return ['already_paid' => true];
            }

            if ($orderStatus === 'ACTIVE') {
                // Order still live — persist the refreshed session
                $payment->update(['gateway_response' => $freshOrder]);

                return [
                    'payment_session_id' => $freshOrder['payment_session_id'] ?? null,
                    'checkout_url'       => $this->cashfree->getCheckoutUrl($freshOrder),
                ];
            }

            // EXPIRED / CANCELLED — mark failed so a new one will be created below
            $payment->update(['status' => 'failed', 'gateway_response' => $freshOrder]);
        } catch (\Exception $e) {
            // 404 or auth error → the order never existed on production
            $this->logError('getFreshCheckoutData: Cashfree getOrder failed', [
                'payment_id' => $payment->id,
                'order_id'   => $payment->order_id,
                'error'      => $e->getMessage(),
            ]);
            $payment->update(['status' => 'failed']);
        }

        // ── Step 2: create a fresh Cashfree order for this user ───────────────
        $user = $payment->user;

        if (!$user) {
            $this->logError('getFreshCheckoutData: user not found for payment', [
                'payment_id' => $payment->id,
            ]);
            return null;
        }

        $result = $this->getOrCreateCheckoutUrl($user, 'web');

        if (empty($result['checkout_url'])) {
            return null;
        }

        return [
            'payment_session_id' => $result['payment_session_id'] ?? null,
            'checkout_url'       => $result['checkout_url'],
        ];
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
