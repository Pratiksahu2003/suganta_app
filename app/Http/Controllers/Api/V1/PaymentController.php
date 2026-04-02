<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Payment;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\CashfreeService;
use App\Services\NotePurchaseService;
use App\Services\RegistrationPaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends BaseApiController
{
    public function __construct(
        protected CashfreeService $cashfree,
        protected RegistrationPaymentService $registrationPaymentService,
        protected SubscriptionService $subscriptionService,
        protected NotePurchaseService $notePurchaseService,
        protected \App\Services\V6\MarketplaceService $marketplaceService
    ) {}

    /**
     * Get authenticated user's payment history.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Payment::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage  = min((int) $request->get('per_page', 15), 50);
        $payments = $query->paginate($perPage);

        $data = $payments->through(function (Payment $payment) {
            return $this->formatPaymentForResponse($payment);
        });

        return $this->success('Payment history retrieved successfully.', [
            'data'  => $data->items(),
            'meta'  => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'from'         => $data->firstItem(),
                'to'           => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last'  => $data->url($data->lastPage()),
                'prev'  => $data->previousPageUrl(),
                'next'  => $data->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get payment status by order_id (for polling after checkout).
     */
    public function status(Request $request): JsonResponse
    {
        $orderId = $request->query('order_id');
        if (!$orderId) {
            return $this->error('Missing order_id parameter.', Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = Auth::user();

        $payment = Payment::where('order_id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$payment) {
            return $this->notFound('Payment not found or access denied.');
        }

        return $this->success('Payment status retrieved.', [
            'order_id' => $payment->order_id,
            'status' => $payment->status,
            'type' => $payment->meta['type'] ?? null,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'processed_at' => $payment->processed_at?->toIso8601String(),
        ]);
    }

    /**
     * Get invoice URL for a successful payment.
     */
    public function invoice(string $orderId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $payment = Payment::query()
            ->where('order_id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$payment) {
            return $this->notFound('Payment not found or access denied.');
        }

        if ($payment->status !== 'success') {
            return $this->error(
                'Invoice is only available for successful payments.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $invoiceUrl = $this->generateInvoiceUrl($payment->order_id);

        return $this->success('Invoice URL generated successfully.', [
            'order_id'    => $payment->order_id,
            'invoice_url' => $invoiceUrl,
            'expires_at'  => now()->addDays(config('invoice.expires_days', 7))->toIso8601String(),
        ]);
    }

    /**
     * Proxy endpoint: resolves a fresh Cashfree checkout session and returns an
     * HTML page that loads the Cashfree JS SDK and calls cashfree.checkout().
     *
     * WHY HTML instead of a raw redirect:
     *   Cashfree API v2023-08-01+ requires the official JS SDK to properly
     *   initialise a checkout session.  Navigating to the session URL directly
     *   (without the SDK) results in "500 / Oops — Something went wrong" on
     *   Cashfree's page.  The SDK sets the session context; only then does the
     *   hosted checkout page load correctly.
     *
     * The payment links we hand out NEVER expire because:
     *   - If Cashfree session is ACTIVE  → SDK opens checkout immediately.
     *   - If Cashfree session EXPIRED    → we create a brand-new order first,
     *     then the SDK opens the new session.
     *
     * Prerequisite: whitelist "www.suganta.in" in your Cashfree Merchant
     *   Dashboard → Developers → Domain Whitelisting.
     *
     * Route: GET /api/v1/payment/checkout   (public — no auth)
     */
    public function checkout(Request $request): \Illuminate\Http\Response|JsonResponse
    {
        $orderId = $request->query('order_id');

        if (!$orderId) {
            return $this->error('Missing order_id parameter.', Response::HTTP_BAD_REQUEST);
        }

        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return $this->notFound('Payment not found.');
        }

        if ($payment->status === 'success') {
            return $this->success('This payment has already been completed.', [
                'order_id' => $orderId,
                'status'   => 'success',
            ]);
        }

        if (in_array($payment->status, ['cancelled', 'refunded'], true)) {
            return $this->error('This payment link is no longer valid.', Response::HTTP_GONE);
        }

        try {
            $paymentType = $payment->meta['type'] ?? '';
            
            if ($paymentType === 'subscription') {
                $checkoutData = $this->subscriptionService->getFreshCheckoutData($payment);
            } elseif ($paymentType === 'note') {
                $checkoutData = $this->notePurchaseService->getFreshCheckoutData($payment);
            } elseif ($paymentType === 'marketplace') {
                $checkoutData = $this->marketplaceService->getFreshCheckoutData($payment);
            } else {
                $checkoutData = $this->registrationPaymentService->getFreshCheckoutData($payment);
            }

            if (!$checkoutData) {
                return $this->error(
                    'Unable to load the payment page. Please log in and try again.',
                    Response::HTTP_SERVICE_UNAVAILABLE
                );
            }

            if (!empty($checkoutData['already_paid'])) {
                return $this->success('Payment already completed.', [
                    'order_id' => $orderId,
                    'status'   => 'success',
                ]);
            }

            $sessionId   = $checkoutData['payment_session_id'] ?? '';
            $checkoutUrl = $checkoutData['checkout_url'] ?? '';
            $mode        = config('cashfree.is_production') ? 'production' : 'sandbox';

            // Serve an HTML page that loads the Cashfree JS SDK and triggers checkout.
            // Falls back to a direct redirect if the SDK throws (e.g. domain not yet
            // whitelisted), so the page is still useful during Cashfree onboarding.
            $html = $this->buildCheckoutHtml($sessionId, $checkoutUrl, $mode);

            return response($html, Response::HTTP_OK)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        } catch (\Exception $e) {
            Log::error('Payment checkout proxy failed', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);

            return $this->error(
                'Unable to load the payment page. Please try again.',
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }
    }

    /**
     * Build the HTML page that loads the Cashfree JS SDK and opens the checkout.
     */
    private function buildCheckoutHtml(string $sessionId, string $fallbackUrl, string $mode): string
    {
        $appName    = e(config('app.name', 'SuGanta'));
        $sessionId  = e($sessionId);
        $fallbackUrl = e($fallbackUrl);
        $mode        = e($mode);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$appName} — Secure Payment</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 48px 40px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,.25);
            max-width: 380px;
            width: 92%;
        }
        .brand {
            font-size: 26px;
            font-weight: 800;
            color: #4f46e5;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .sub { color: #6b7280; font-size: 13px; margin-bottom: 36px; }
        .spinner {
            width: 52px; height: 52px;
            border: 5px solid #ede9fe;
            border-top-color: #4f46e5;
            border-radius: 50%;
            animation: spin .75s linear infinite;
            margin: 0 auto 22px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .msg  { color: #374151; font-size: 15px; margin-bottom: 8px; }
        .note { color: #9ca3af; font-size: 12px; margin-top: 24px; }
        .note svg { vertical-align: middle; margin-right: 4px; }
        .retry-btn {
            display: none; margin-top: 20px;
            background: #4f46e5; color: #fff;
            border: none; border-radius: 10px;
            padding: 12px 28px; font-size: 15px;
            cursor: pointer; width: 100%;
        }
        .retry-btn:hover { background: #4338ca; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand">{$appName}</div>
    <div class="sub">Secure Payment Gateway</div>

    <div class="spinner" id="spin"></div>
    <p class="msg" id="msg">Loading payment page&hellip;</p>

    <button class="retry-btn" id="retryBtn" onclick="openCheckout()">
        Open Payment Page
    </button>

    <p class="note">
        <svg width="12" height="14" viewBox="0 0 12 14" fill="none"
             xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="1" y="6" width="10" height="8" rx="2"
                  stroke="#9ca3af" stroke-width="1.5"/>
            <path d="M3 6V4a3 3 0 0 1 6 0v2" stroke="#9ca3af" stroke-width="1.5"
                  stroke-linecap="round"/>
        </svg>
        Secured by Cashfree Payments
    </p>
</div>

<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<script>
(function () {
    var SESSION_ID   = "{$sessionId}";
    var FALLBACK_URL = "{$fallbackUrl}";
    var MODE         = "{$mode}";

    function showError(txt) {
        document.getElementById('spin').style.display = 'none';
        document.getElementById('msg').textContent    = txt;
        document.getElementById('retryBtn').style.display = 'block';
    }

    window.openCheckout = function () {
        document.getElementById('spin').style.display  = 'block';
        document.getElementById('retryBtn').style.display = 'none';
        document.getElementById('msg').textContent = 'Loading payment page\u2026';

        try {
            if (typeof Cashfree === 'undefined') {
                throw new Error('SDK not loaded');
            }
            var cashfree = Cashfree({ mode: MODE });
            cashfree.checkout({
                paymentSessionId: SESSION_ID,
                redirectTarget:   '_self'
            });
        } catch (err) {
            // SDK unavailable (e.g. domain not yet whitelisted) — direct redirect
            if (FALLBACK_URL) {
                window.location.href = FALLBACK_URL;
            } else {
                showError('Unable to open payment page. Please try again.');
            }
        }
    };

    // Kick off immediately when DOM + SDK are ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.openCheckout);
    } else {
        window.openCheckout();
    }
})();
</script>
</body>
</html>
HTML;
    }

    /**
     * Handle Cashfree payment return URL (called after user completes / abandons payment).
     *
     * Cashfree redirects the user's browser here after the hosted checkout flow.
     * We verify the current order status with Cashfree and return a JSON status
     * that the frontend / mobile app can use to navigate the user to the right screen.
     *
     * Route: GET /api/v1/payment/callback   (public — no auth)
     */
    public function callback(Request $request): JsonResponse
    {
        $orderId = $request->query('order_id');

        if (!$orderId) {
            return $this->error('Missing order_id parameter.', Response::HTTP_BAD_REQUEST);
        }

        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return $this->notFound('Payment record not found.');
        }

        // Already in a terminal state — return immediately (idempotent)
        if (in_array($payment->status, ['success', 'failed', 'cancelled', 'refunded'], true)) {
            return $this->success('Payment status retrieved.', [
                'order_id'     => $orderId,
                'status'       => $payment->status,
                'processed_at' => $payment->processed_at?->toIso8601String(),
            ]);
        }

        // Fetch live order status from Cashfree to check whether payment went through
        try {
            $orderData   = $this->cashfree->getOrder($orderId);
            $orderStatus = strtoupper($orderData['order_status'] ?? '');

            if ($orderStatus === 'PAID') {
                // Fetch payment-level details for the reference ID / method
                $cfPaymentData = [];
                try {
                    $payments      = $this->cashfree->getOrderPayments($orderId);
                    $cfPaymentData = collect($payments)
                        ->firstWhere('payment_status', 'SUCCESS') ?? [];
                } catch (\Exception $e) {
                    Log::warning('callback: could not fetch order payments from Cashfree', [
                        'order_id' => $orderId,
                        'error'    => $e->getMessage(),
                    ]);
                }

                $paymentType = $payment->meta['type'] ?? '';
                if ($paymentType === 'subscription') {
                    $this->subscriptionService->processSuccessfulPayment($payment, $cfPaymentData);
                } elseif ($paymentType === 'note') {
                    $this->notePurchaseService->processSuccessfulPayment($payment, $cfPaymentData);
                } else {
                    $this->registrationPaymentService->handlePaymentSuccess($orderId, $cfPaymentData);
                }

                return $this->success('Payment successful.', [
                    'order_id' => $orderId,
                    'status'   => 'success',
                ]);
            }

            if (in_array($orderStatus, ['EXPIRED', 'CANCELLED'], true)) {
                $paymentType = $payment->meta['type'] ?? '';
                if ($paymentType === 'subscription') {
                    $payment->update(['status' => 'failed', 'processed_at' => now(), 'gateway_response' => array_merge($payment->gateway_response ?? [], ['payment_data' => $orderData])]);
                } elseif ($paymentType === 'note') {
                    $payment->update(['status' => 'failed', 'processed_at' => now(), 'gateway_response' => array_merge($payment->gateway_response ?? [], ['payment_data' => $orderData])]);
                } else {
                    $this->registrationPaymentService->handlePaymentFailure($orderId, $orderData);
                }

                return $this->success('Payment could not be completed.', [
                    'order_id' => $orderId,
                    'status'   => 'failed',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Payment callback verification failed', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
        }

        // Payment still pending — return current local status
        return $this->success('Payment is pending.', [
            'order_id' => $orderId,
            'status'   => $payment->status,
        ]);
    }

    /**
     * Handle Cashfree webhook notifications (server-to-server, background).
     *
     * Verifies the HMAC-SHA256 signature before processing any event.
     *
     * Handled events:
     *   PAYMENT_SUCCESS_WEBHOOK
     *   PAYMENT_FAILED_WEBHOOK
     *   PAYMENT_USER_DROPPED_WEBHOOK
     *
     * Route: POST /api/v1/payment/webhook   (public — no auth)
     */
    public function webhook(Request $request): JsonResponse
    {
        // Use preserved raw body when available (middleware stores it for signature verification)
        $rawBody   = $request->attributes->get('raw_body') ?? $request->getContent();
        $signature = $request->header('x-webhook-signature', '');
        $timestamp = $request->header('x-webhook-timestamp', '');

        // Enhanced logging for debugging
        Log::info('Cashfree webhook received', [
            'ip' => $request->ip(),
            'headers' => [
                'x-webhook-signature' => $signature,
                'x-webhook-timestamp' => $timestamp,
                'content-type' => $request->header('content-type'),
                'user-agent' => $request->header('user-agent'),
            ],
            'body_length' => strlen($rawBody),
            'body_preview' => substr($rawBody, 0, 200) . (strlen($rawBody) > 200 ? '...' : ''),
        ]);

        if (config('cashfree.webhook_skip_verify')) {
            Log::warning('Cashfree webhook: signature verification SKIPPED (CASHFREE_WEBHOOK_SKIP_VERIFY is enabled — disable in production!)');
        } elseif (!$this->cashfree->verifyWebhookSignature($rawBody, $signature, $timestamp)) {
            Log::warning('Cashfree webhook: signature verification failed', [
                'ip'            => $request->ip(),
                'has_signature' => $signature !== '',
                'has_timestamp' => $timestamp !== '',
                'body_length'   => strlen($rawBody),
            ]);

            return $this->error('Invalid webhook signature.', Response::HTTP_BAD_REQUEST);
        }

        $payload   = $request->json()->all();
        $eventType = $payload['type'] ?? '';
        $orderId   = $this->extractOrderIdFromWebhookPayload($payload);
        $paymentData = $payload['data']['payment'] ?? $payload['data']['charge'] ?? [];

        if (empty($orderId)) {
            Log::info('Cashfree webhook received (no order_id)', ['event_type' => $eventType]);
            return response()->json(['message' => 'Webhook received.'], Response::HTTP_OK);
        }

        Log::info('Cashfree webhook received', [
            'event_type' => $eventType,
            'order_id'   => $orderId,
        ]);

        switch ($eventType) {
            case 'PAYMENT_SUCCESS_WEBHOOK':
                $this->handlePaymentSuccess($orderId, $paymentData);
                break;

            case 'PAYMENT_FAILED_WEBHOOK':
            case 'PAYMENT_USER_DROPPED_WEBHOOK':
                $this->handlePaymentFailure($orderId, $paymentData);
                break;

            case 'PAYMENT_CHARGES_WEBHOOK':
                // Charge-related events: treat as success when payment_status indicates success
                $paymentStatus = strtoupper($paymentData['payment_status'] ?? $payload['data']['payment_status'] ?? '');
                if ($paymentStatus === 'SUCCESS' || $paymentStatus === 'PAID') {
                    $this->handlePaymentSuccess($orderId, $paymentData);
                } else {
                    $this->handlePaymentFailure($orderId, $paymentData);
                }
                break;

            case 'REFUND_STATUS_WEBHOOK':
                $this->handleRefundStatus($orderId, $paymentData);
                break;

            default:
                Log::info('Cashfree webhook: unhandled event type', ['type' => $eventType]);
                break;
        }

        return response()->json(['message' => 'Webhook processed.'], Response::HTTP_OK);
    }

    /**
     * Handle payment success for any payment type (registration or subscription).
     */
    private function handlePaymentSuccess(string $orderId, array $paymentData = []): void
    {
        $payment = Payment::where('order_id', $orderId)->first();
        
        if (!$payment) {
            Log::error('Payment success webhook: payment record not found', ['order_id' => $orderId]);
            return;
        }

        $paymentType = $payment->meta['type'] ?? '';

        switch ($paymentType) {
            case 'registration':
                $this->registrationPaymentService->handlePaymentSuccess($orderId, $paymentData);
                break;
                
            case 'subscription':
                try {
                    $this->subscriptionService->processSuccessfulPayment($payment, $paymentData);
                    Log::info('Subscription payment processed successfully', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to process subscription payment', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                break;

            case 'note':
                try {
                    $this->notePurchaseService->processSuccessfulPayment($payment, $paymentData);
                    Log::info('Note purchase payment processed successfully', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to process note purchase payment', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                break;

            case 'marketplace':
                try {
                    $this->marketplaceService->processSuccessfulPayment($payment, $paymentData);
                    Log::info('Marketplace payment processed successfully', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to process marketplace payment', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                break;
                
            default:
                Log::warning('Payment success webhook: unknown payment type', [
                    'order_id' => $orderId,
                    'payment_type' => $paymentType,
                ]);
                break;
        }
    }

    /**
     * Handle payment failure for any payment type (registration or subscription).
     */
    private function handlePaymentFailure(string $orderId, array $paymentData = []): void
    {
        $payment = Payment::where('order_id', $orderId)->first();
        
        if (!$payment) {
            Log::error('Payment failure webhook: payment record not found', ['order_id' => $orderId]);
            return;
        }

        $paymentType = $payment->meta['type'] ?? '';

        switch ($paymentType) {
            case 'registration':
                $this->registrationPaymentService->handlePaymentFailure($orderId, $paymentData);
                break;
                
            case 'subscription':
                $payment->update([
                    'status' => 'failed',
                    'processed_at' => now(),
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        ['payment_data' => $paymentData]
                    ),
                ]);
                
                Log::info('Subscription payment failed', [
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                ]);
                break;

            case 'note':
                $payment->update([
                    'status' => 'failed',
                    'processed_at' => now(),
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        ['payment_data' => $paymentData]
                    ),
                ]);
                
                Log::info('Note purchase payment failed', [
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                ]);
                break;

            case 'marketplace':
                $payment->update([
                    'status' => 'failed',
                    'processed_at' => now(),
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        ['payment_data' => $paymentData]
                    ),
                ]);
                
                Log::info('Marketplace payment failed', [
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                ]);
                break;
                
            default:
                Log::warning('Payment failure webhook: unknown payment type', [
                    'order_id' => $orderId,
                    'payment_type' => $paymentType,
                ]);
                break;
        }
    }

    /**
     * Handle refund status for any payment type (registration or subscription).
     */
    private function handleRefundStatus(string $orderId, array $paymentData = []): void
    {
        $payment = Payment::where('order_id', $orderId)->first();
        
        if (!$payment) {
            Log::error('Refund status webhook: payment record not found', ['order_id' => $orderId]);
            return;
        }

        $paymentType = $payment->meta['type'] ?? '';

        switch ($paymentType) {
            case 'registration':
                $this->registrationPaymentService->handleRefundStatus($orderId, $paymentData);
                break;
                
            case 'subscription':
                // Handle subscription refund
                $refundStatus = strtoupper($paymentData['refund_status'] ?? $paymentData['status'] ?? '');
                
                if (in_array($refundStatus, ['SUCCESS', 'PROCESSED', 'COMPLETED'], true)) {
                    $payment->update([
                        'status' => 'refunded',
                        'processed_at' => now(),
                        'gateway_response' => array_merge(
                            $payment->gateway_response ?? [],
                            [
                                'refund_data' => $paymentData,
                                'refund_processed_at' => now()->toISOString()
                            ]
                        ),
                    ]);

                    // Cancel the associated subscription
                    $subscription = UserSubscription::where('payment_id', $payment->id)->first();
                    if ($subscription) {
                        $subscription->update(['status' => 'refunded']);
                        
                        Log::info('Subscription refunded and cancelled', [
                            'order_id' => $orderId,
                            'subscription_id' => $subscription->id,
                        ]);
                    }
                }
                break;

            case 'note':
                $refundStatus = strtoupper($paymentData['refund_status'] ?? $paymentData['status'] ?? '');
                
                if (in_array($refundStatus, ['SUCCESS', 'PROCESSED', 'COMPLETED'], true)) {
                    $payment->update([
                        'status' => 'refunded',
                        'processed_at' => now(),
                        'gateway_response' => array_merge(
                            $payment->gateway_response ?? [],
                            [
                                'refund_data' => $paymentData,
                                'refund_processed_at' => now()->toISOString()
                            ]
                        ),
                    ]);

                    $notePurchase = \App\Models\NotePurchase::where('payment_id', $payment->id)->first();
                    if ($notePurchase) {
                        $notePurchase->update(['status' => 'refunded']);
                        
                        Log::info('Note purchase refunded', [
                            'order_id' => $orderId,
                            'note_purchase_id' => $notePurchase->id,
                        ]);
                    }
                }
                break;
                
            default:
                Log::warning('Refund status webhook: unknown payment type', [
                    'order_id' => $orderId,
                    'payment_type' => $paymentType,
                ]);
                break;
        }
    }

    /**
     * Extract order_id from Cashfree webhook payload (supports multiple payload structures).
     */
    private function extractOrderIdFromWebhookPayload(array $payload): string
    {
        $order = $payload['data']['order'] ?? [];
        if (is_array($order) && !empty($order['order_id'])) {
            return (string) $order['order_id'];
        }

        if (!empty($payload['data']['order_id'])) {
            return (string) $payload['data']['order_id'];
        }

        $charge = $payload['data']['charge'] ?? [];
        if (is_array($charge) && !empty($charge['order_id'])) {
            return (string) $charge['order_id'];
        }

        return '';
    }

    /**
     * Format a payment record for API response.
     */
    private function formatPaymentForResponse(Payment $payment): array
    {
        $data = [
            'id'           => $payment->id,
            'order_id'     => $payment->order_id,
            'reference_id' => $payment->reference_id,
            'currency'     => $payment->currency,
            'amount'       => (float) $payment->amount,
            'status'       => $payment->status,
            'type'         => $payment->meta['type'] ?? null,
            'description'  => $payment->meta['description'] ?? $payment->meta['note_name'] ?? $payment->meta['plan_name'] ?? null,
            'created_at'   => $payment->created_at->toIso8601String(),
            'processed_at' => $payment->processed_at?->toIso8601String(),
        ];

        if (($payment->meta['type'] ?? '') === 'note') {
            $data['note_id'] = $payment->meta['note_id'] ?? null;
            $data['note_name'] = $payment->meta['note_name'] ?? null;
        }

        if (($payment->meta['type'] ?? '') === 'subscription') {
            $data['subscription_plan_id'] = $payment->meta['subscription_plan_id'] ?? null;
            $data['plan_name'] = $payment->meta['plan_name'] ?? null;
        }

        if ($payment->status === 'success') {
            $data['invoice_url'] = $this->generateInvoiceUrl($payment->order_id);
        }

        return $data;
    }

    /**
     * Generate a signed temporary invoice URL.
     */
    private function generateInvoiceUrl(string $orderId): string
    {
        $baseUrl    = rtrim(config('invoice.base_url'), '/');
        $expiration = now()->addDays(config('invoice.expires_days', 7));

        $originalUrl = URL::to('');
        URL::forceRootUrl($baseUrl);
        URL::forceScheme(parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https');

        try {
            $signedUrl = URL::temporarySignedRoute(
                'payments.invoice',
                $expiration,
                ['orderId' => $orderId]
            );
        } finally {
            URL::forceRootUrl($originalUrl);
        }

        return $signedUrl;
    }
}
