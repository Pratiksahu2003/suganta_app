<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL where invoice pages are served (e.g. suganta.com).
    | Used to generate signed invoice URLs for successful payments.
    |
    */

    'base_url' => env('INVOICE_BASE_URL', 'https://www.suganta.com'),

    /*
    |--------------------------------------------------------------------------
    | Invoice URL Validity ( Days )
    |--------------------------------------------------------------------------
    |
    | Number of days that the signed invoice URL remains valid.
    |
    */

    'expires_days' => (int) env('INVOICE_EXPIRES_DAYS', 7),

];
