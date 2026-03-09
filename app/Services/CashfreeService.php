<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CashfreeService
{
    protected string $baseUrl;
    protected string $appId;
    protected string $secretKey;
    protected string $apiVersion;
    protected bool $isProduction;

    public function __construct()
    {
        $this->appId        = config('cashfree.app_id', '');
        $this->secretKey    = config('cashfree.secret_key', '');
        $this->isProduction = config('cashfree.is_production', false);
        $this->apiVersion   = config('cashfree.api_version', '2022-09-01');

        $this->baseUrl = $this->isProduction
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    /**
     * Build the order payload for Cashfree order creation.
     */
    public function buildOrderPayload(
        string $orderId,
        string $customerId,
        string $customerEmail,
        string $customerPhone,
        float $orderAmount,
        string $orderCurrency
    ): array {
        $configReturnUrl = config('cashfree.return_url', '');
        $returnUrl = $configReturnUrl
            ? rtrim($configReturnUrl, '/') . '?order_id=' . $orderId
            : url('/api/v1/payment/callback') . '?order_id=' . $orderId;

        return [
            'order_id'         => $orderId,
            'order_amount'     => $orderAmount,
            'order_currency'   => $orderCurrency,
            'customer_details' => [
                'customer_id'    => $customerId,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
            ],
            'order_meta' => [
                'return_url' => $returnUrl,
                'notify_url' => url('/api/v1/payment/webhook'),
            ],
        ];
    }

    /**
     * Create a new order on Cashfree.
     */
    public function createOrder(array $payload): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post($this->baseUrl . '/orders', $payload);

        if ($response->successful()) {
            return $response->json();
        }

        $this->logError('Cashfree Create Order Failed', [
            'status'  => $response->status(),
            'body'    => $response->body(),
            'payload' => $payload,
        ]);

        throw new \Exception('Cashfree Order Creation Failed: ' . $response->body());
    }

    /**
     * Fetch an existing order from Cashfree.
     */
    public function getOrder(string $orderId): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get($this->baseUrl . '/orders/' . $orderId);

        if ($response->successful()) {
            return $response->json();
        }

        $this->logError('Cashfree Get Order Failed', [
            'status'   => $response->status(),
            'body'     => $response->body(),
            'order_id' => $orderId,
        ]);

        throw new \Exception('Cashfree Get Order Failed: ' . $response->body());
    }

    /**
     * Fetch all payments for an order from Cashfree.
     * Returns an array of payment objects.
     */
    public function getOrderPayments(string $orderId): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get($this->baseUrl . '/orders/' . $orderId . '/payments');

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $this->logError('Cashfree Get Order Payments Failed', [
            'status'   => $response->status(),
            'body'     => $response->body(),
            'order_id' => $orderId,
        ]);

        throw new \Exception('Cashfree Get Order Payments Failed: ' . $response->body());
    }

    /**
     * Derive the hosted checkout URL from an order response.
     *
     * Handles both:
     *   - API v2022-09-01: response contains `payment_link`
     *   - API v2023-08-01 / v2025-01-01: response contains `payment_session_id`
     */
    public function getCheckoutUrl(array $orderResponse): ?string
    {
        // Older API versions (2022-09-01) return payment_link directly
        if (!empty($orderResponse['payment_link'])) {
            return $orderResponse['payment_link'];
        }

        // Newer API versions (2023-08-01+, 2025-01-01) return payment_session_id
        if (!empty($orderResponse['payment_session_id'])) {
            return $this->buildHostedPageUrl($orderResponse['payment_session_id']);
        }

        return null;
    }

    /**
     * Verify the HMAC-SHA256 signature from a Cashfree webhook.
     *
     * Cashfree signs webhooks as:
     *   base64( HMAC_SHA256( timestamp + rawBody, clientSecret ) )
     */
    public function verifyWebhookSignature(string $rawBody, string $signature, string $timestamp): bool
    {
        $secret = config('cashfree.webhook_secret') ?: $this->secretKey;

        if (empty($secret) || empty($signature) || empty($timestamp)) {
            return false;
        }

        $computed = base64_encode(
            hash_hmac('sha256', $timestamp . $rawBody, $secret, true)
        );

        return hash_equals($computed, $signature);
    }

    /**
     * Check whether an order's status from Cashfree indicates the payment was successful.
     */
    public function isOrderPaid(array $orderData): bool
    {
        return strtoupper($orderData['order_status'] ?? '') === 'PAID';
    }

    /**
     * Build the hosted payment page URL from a payment_session_id.
     *
     * Production: https://payments.cashfree.com/order/#/{session_id}
     * Sandbox:    https://sandbox.cashfree.com/order/#/{session_id}
     */
    private function buildHostedPageUrl(string $paymentSessionId): string
    {
        if ($this->isProduction) {
            return 'https://payments.cashfree.com/order/#/' . $paymentSessionId;
        }

        return 'https://sandbox.cashfree.com/order/#/' . $paymentSessionId;
    }

    private function getHeaders(): array
    {
        return [
            'x-client-id'     => $this->appId,
            'x-client-secret' => $this->secretKey,
            'x-api-version'   => $this->apiVersion,
            'Content-Type'    => 'application/json',
        ];
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
