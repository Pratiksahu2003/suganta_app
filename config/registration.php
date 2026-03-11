<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Registration Charges Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file stores registration-related charges for each role.
    | Each role has an actual price and a discounted price.
    |
    */

    'charges' => [
        'student' => [
            'actual_price' => 0.00,
            'discounted_price' => 0.00,
            'currency' => 'INR',
            'description' => 'Student Registration Fee',
        ],
        'teacher' => [
            'actual_price' => 1000.00,
            'discounted_price' => 1.00,
            'currency' => 'INR',
            'description' => 'Teacher Registration Fee',
        ],
        'institute' => [
            'actual_price' => 3000.00,
            'discounted_price' => 599.00,
            'currency' => 'INR',
            'description' => 'Institute Registration Fee',
        ],
        'university' => [
            'actual_price' => 5000.00,
            'discounted_price' => 699.00,
            'currency' => 'INR',
            'description' => 'University Registration Fee',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Payment Settings
    |--------------------------------------------------------------------------
    |
    | Settings related to registration payment processing
    |
    */

    'payment' => [
        'enabled' => env('REGISTRATION_PAYMENT_ENABLED', true),
        'required_for_roles' => ['teacher', 'institute', 'university'], // Roles that require payment
        'free_roles' => ['student'], // Roles that don't require payment
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Status Flow
    |--------------------------------------------------------------------------
    |
    | The registration process follows these steps:
    | 1. User registers -> account created with status 'pending_email_verification'
    | 2. User verifies email -> status changes to 'pending_payment' (if payment required)
    | 3. User pays registration fee -> status changes to 'verified' and account is activated
    |
    */

    'status_flow' => [
        'pending_email_verification' => 'Email verification pending',
        'pending_payment' => 'Registration payment pending',
        'verified' => 'Account fully verified and active',
    ],
];
