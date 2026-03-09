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
     *
     * URL construction deliberately extracts only the scheme+host from APP_URL
     * so that it stays correct even when APP_URL already contains a path suffix
     * like "/api/v1" (which would otherwise produce doubled segments).
     */
    public function buildOrderPayload(
        string $orderId,
        string $customerId,
        string $customerEmail,
        string $customerPhone,
        float $orderAmount,
        string $orderCurrency
    ): array {
        $baseUrl = $this->deriveBaseUrl();

        $configReturnUrl = config('cashfree.return_url', '');
        $returnUrl = $configReturnUrl
            ? rtrim($configReturnUrl, '/') . '?order_id=' . $orderId
            : $baseUrl . '/api/v1/payment/callback?order_id=' . $orderId;

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
                'notify_url' => $baseUrl . '/api/v1/payment/webhook',
            ],
        ];
    }

    /**
     * Derive the scheme + host (+ optional port) from APP_URL, ignoring any
     * path component.  This prevents doubled path segments when APP_URL is
     * set to something like "https://www.suganta.in/api/v1".
     */
    private function deriveBaseUrl(): string
    {
        $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
        $parsed = parse_url($appUrl);

        $base  = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'localhost');

        if (!empty($parsed['port'])) {
            $base .= ':' . $parsed['port'];
        }

        return $base;
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
     * Cashfree: signedPayload = timestamp + rawBody, signature = Base64(HMAC-SHA256(signedPayload, secret))
     * Tries both timestamp+body and body+timestamp; some Cashfree setups use alternate order.
     *
     * @see https://www.cashfree.com/docs/payments/online/webhooks/signature-verification
     */
    public function verifyWebhookSignature(string $rawBody, string $signature, string $timestamp): bool
    {
        $signature = trim($signature);
        $timestamp = trim($timestamp);

        if ($signature === '' || $timestamp === '') {
            return false;
        }

        $secret = config('cashfree.webhook_secret') ?: $this->secretKey;
        $secret = is_string($secret) ? trim($secret) : $this->secretKey;

        if ($secret === '') {
            return false;
        }

        // Prefer Cashfree SDK verification when available (avoids subtle format differences).
        // Use dynamic class name to avoid hard dependency during static analysis.
        //
        // IMPORTANT: The installed SDK in this repo (cashfree/cashfree-pg v5.0.3) uses
        // a constructor with params (environment, clientId, clientSecret, ...).
        $cashfreeClass = '\\Cashfree\\Cashfree';
        if (class_exists($cashfreeClass)) {
            try {
                // SDK uses 0 = SANDBOX, 1 = PRODUCTION
                $env = $this->isProduction ? 1 : 0;

                /** @var object $cashfree */
                $cashfree = new $cashfreeClass($env, $this->appId, $secret, '', '', '', false);
                $cashfree->PGVerifyWebhookSignature($signature, $rawBody, $timestamp);

                return true;
            } catch (\Throwable $e) {
                if (config('app.debug')) {
                    Log::debug('Cashfree webhook: SDK signature verification failed (falling back to manual)', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $verify = function (string $signedPayload) use ($secret, $signature): bool {
            $computed = base64_encode(hash_hmac('sha256', $signedPayload, $secret, true));
            return hash_equals($computed, $signature);
        };

        // Try all documented variants (Cashfree docs differ: some use dot, some don't)
        foreach ([$timestamp . $rawBody, $timestamp . '.' . $rawBody, $rawBody . $timestamp] as $payload) {
            if ($verify($payload)) {
                return true;
            }
        }

        if (config('app.debug')) {
            $computed = [
                'ts+body'      => base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $secret, true)),
                'ts.+body'     => base64_encode(hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret, true)),
                'body+ts'      => base64_encode(hash_hmac('sha256', $rawBody . $timestamp, $secret, true)),
                // Helps confirm we’re using the same secret as your dashboard (no secret leakage).
                'secret_hash'  => substr(hash('sha256', $secret), 0, 16),
                'secret_last4' => substr($secret, -4),
                'raw_sha256'   => hash('sha256', $rawBody),
                'raw_len'      => strlen($rawBody),
                'ts_len'       => strlen($timestamp),
                'sig_len'      => strlen($signature),
            ];
            Log::debug('Cashfree webhook: signature mismatch debug', [
                'timestamp'          => $timestamp,
                'received_signature' => $signature,
                'computed'           => $computed,
            ]);
        }

        return false;
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
