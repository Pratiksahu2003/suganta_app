<?php

return [
    'app_id'         => env('CASHFREE_APP_ID', ''),
    'secret_key'     => env('CASHFREE_SECRET_KEY', ''),
    'is_production'  => env('CASHFREE_ENV', 'sandbox') === 'production',
    'api_version'    => env('CASHFREE_API_VERSION', '2022-09-01'),
    'return_url'     => env('CASHFREE_RETURN_URL', ''),
    'go_back_url'    => env('PAYMENT_GO_BACK_URL', env('FRONTEND_URL', 'https://www.suganta.com')),
    'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET', env('CASHFREE_SECRET_KEY', '')),
    // ONLY when APP_DEBUG=true: set CASHFREE_WEBHOOK_SKIP_VERIFY=true to bypass verification (for debugging)
    'webhook_skip_verify' => env('APP_DEBUG', false) && env('CASHFREE_WEBHOOK_SKIP_VERIFY', false),
];
