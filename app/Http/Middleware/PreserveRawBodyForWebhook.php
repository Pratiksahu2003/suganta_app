<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Preserve the raw request body for webhook signature verification.
 *
 * Must run before any middleware that parses the body. Stores the raw content
 * so the webhook controller can verify Cashfree's HMAC against the exact bytes
 * received, not a re-encoded or modified version.
 */
class PreserveRawBodyForWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('raw_body', $request->getContent());

        return $next($request);
    }
}
