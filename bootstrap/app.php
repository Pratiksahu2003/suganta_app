<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Run in global stack so we read php://input BEFORE any other code (Symfony, etc.)
        $middleware->prepend(\App\Http\Middleware\PreserveRawBodyForWebhook::class);
        // Sanctum: session + CSRF for first-party SPA (Origin/Referer in SANCTUM_STATEFUL_DOMAINS); Bearer tokens for other clients
        $middleware->statefulApi();
        $middleware->prependToGroup('api', \App\Http\Middleware\ForceJsonApi::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Ensure API requests always receive consistent JSON error responses
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Determine status code
                $statusCode = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                    ? $e->getStatusCode()
                    : ($e instanceof \Illuminate\Auth\AuthenticationException ? 401 : 500);

                // Handle Validation Exceptions
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'message' => $e->getMessage(),
                        'success' => false,
                        'code' => 422,
                        'errors' => $e->errors(),
                    ], 422);
                }

                // Default Error Response
                $message = config('app.debug') && $statusCode >= 500 ? $e->getMessage() : ($statusCode == 500 ? 'Server Error' : $e->getMessage());
                
                return response()->json([
                    'message' => $message,
                    'success' => false,
                    'code' => $statusCode,
                ], $statusCode);
            }
        });
    })->create();
