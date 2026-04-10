<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;
use Illuminate\Http\Request;

class ValidateCsrfToken extends Middleware
{
    /**
     * Fallback when SANCTUM_CSRF_EXEMPT_API=false: public auth may run before any session exists.
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
     * Allow all JSON API routes to skip CSRF so both session cookies and Bearer tokens work.
     * When disabled in config, only paths in $except (and Bearer bypass) apply.
     */
    protected function inExceptArray($request): bool
    {
        if (filter_var(config('sanctum.csrf_exempt_api', true), FILTER_VALIDATE_BOOLEAN)
            && $request instanceof Request
            && $this->isApiPath($request)) {
            return true;
        }

        return parent::inExceptArray($request);
    }

    protected function isApiPath(Request $request): bool
    {
        $path = $request->path();

        return $path === 'api' || str_starts_with($path, 'api/');
    }

    /**
     * Extra bypass when CSRF is not exempt and the client sends a Bearer token.
     */
    protected function tokensMatch($request): bool
    {
        if ($request instanceof Request && $this->usesBearerAuthentication($request)) {
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
