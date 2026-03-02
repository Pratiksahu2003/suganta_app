<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Default version prefix used in routes (e.g. /api/v1). Bump this when
    | you introduce breaking changes and keep v1 routes for backward compatibility.
    |
    */
    'version' => env('API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Default throttle for API routes: '60,1' = 60 requests per minute.
    | Configure in RouteServiceProvider or bootstrap/app.php if needed.
    |
    */
    'rate_limit' => env('API_RATE_LIMIT', '60,1'),

    /*
    |--------------------------------------------------------------------------
    | Response Keys
    |--------------------------------------------------------------------------
    |
    | Keys used in standard API JSON responses. Keep consistent for clients.
    |
    */
    'response_keys' => [
        'success' => 'success',
        'message' => 'message',
        'data' => 'data',
        'errors' => 'errors',
        'meta' => 'meta',
    ],

];
