<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
    | When supports_credentials is true, browsers require explicit origins (not *).
    | Set CORS_ALLOWED_ORIGINS to a comma-separated list, e.g. https://app.suganta.com
    | FRONTEND_URL is merged in automatically when set.
    */
    'allowed_origins' => (static function (): array {
        $origins = array_values(array_unique(array_filter(array_merge(
            array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),
            [env('FRONTEND_URL')],
        ))));

        if ($origins === [] && (string) env('APP_ENV', '') === 'local') {
            return ['http://deshboard.test', 'http://127.0.0.1:3000', 'http://localhost:5173'];
        }

        return $origins;
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => filter_var(env('CORS_SUPPORTS_CREDENTIALS', true), FILTER_VALIDATE_BOOLEAN),

];
