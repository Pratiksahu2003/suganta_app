<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Preserve the raw request body for Cashfree webhook signature verification.
 *
 * Cashfree requires the EXACT raw payload bytes for HMAC verification.
 * Reads via Request::getContent() early so Symfony/Laravel cache the body
 * (prevents the stream from being consumed before JSON parsing later).
 *
 * @see https://www.cashfree.com/docs/payments/online/webhooks/signature-verification
 */
class PreserveRawBodyForWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        // Match both "/api/v1/payment/webhook" and "/api/v1/payment/webhook/".
        if (!$request->is('api/v1/payment/webhook*')) {
            return $next($request);
        }

        // Read raw body BEFORE any parsing. getContent() will also cache it
        // for downstream JSON parsing (important for webhook processing).
        $rawBody = $request->getContent();

        $request->attributes->set('raw_body', $rawBody);

        return $next($request);
    }
}
