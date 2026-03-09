<?php

return [
    'app_id'         => env('CASHFREE_APP_ID', ''),
    'secret_key'     => env('CASHFREE_SECRET_KEY', ''),
    'is_production'  => env('CASHFREE_ENV', 'sandbox') === 'production',
    'api_version'    => env('CASHFREE_API_VERSION', '2022-09-01'),
    'return_url'     => env('CASHFREE_RETURN_URL', ''),
    'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET', env('CASHFREE_SECRET_KEY', '')),
];
