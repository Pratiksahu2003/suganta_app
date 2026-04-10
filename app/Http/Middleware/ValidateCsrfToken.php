<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;
use Illuminate\Http\Request;

class ValidateCsrfToken extends Middleware
{
    /**
     * Public JSON auth endpoints: browsers often POST here before any session/XSRF cookie exists.
     * Authenticated routes and the rest of the API still require CSRF for cookie-only requests,
     * unless a Bearer token is present (see tokensMatch).
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*/auth/register',
        'api/*/auth/login',
        'api/*/auth/login/*',
        'api/*/auth/forgot-password',
        'api/*/auth/reset-password',
    ];

    /**
     * SPA / browser cookie auth must still send X-XSRF-TOKEN (after GET /sanctum/csrf-cookie).
     * Requests that authenticate with a Bearer token do not need CSRF protection; skipping here
     * fixes 419 for mobile, Postman, and WebViews that send a first-party Origin but no CSRF header.
     */
    protected function tokensMatch($request): bool
    {
        if ($this->usesBearerAuthentication($request)) {
            return true;
        }

        return parent::tokensMatch($request);
    }

    protected function usesBearerAuthentication(Request $request): bool
    {
        if (! filter_var(config('sanctum.csrf_skip_with_bearer', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $header = $request->header('Authorization', '');
        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return false;
        }

        return trim(substr($header, 7)) !== '';
    }
}
