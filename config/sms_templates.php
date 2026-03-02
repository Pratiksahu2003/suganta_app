<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Templates Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all SMS templates used throughout the application.
    | Templates support dynamic variables using {{variable_name}} syntax.
    |
    */

    'templates' => [
        
        // DLT Approved Templates Only
        'dlt_otp_verification' => [
            'name' => 'DLT OTP Verification',
            'content' => '{{otp}} is your verification code for Suganta Tutors. It expires in 5 minutes.',
            'required_variables' => ['otp'],
            'category' => 'dlt_authentication',
            'dlt_template_id' => 'DLT_OTP_001'
        ],

        'dlt_profile_completion' => [
            'name' => 'DLT Profile Completion',
            'content' => 'Hi {{name}}, your Suganta.com registration is almost complete! Don\'t miss out. Finish your profile today to fully activate your account. www.suganta.com/{{profile_link}}',
            'required_variables' => ['name', 'profile_link'],
            'category' => 'dlt_onboarding',
            'dlt_template_id' => 'DLT_PROFILE_001'
        ],

        'dlt_student_lead' => [
            'name' => 'DLT Student Lead',
            'content' => 'URGENT: New student query. Student: {{student_name}}, M.no:{{mobile}}. Check your portal now. www.suganta.com/{{portal_link}}',
            'required_variables' => ['student_name', 'mobile', 'portal_link'],
            'category' => 'dlt_leads',
            'dlt_template_id' => 'DLT_LEAD_001'
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Template Categories
    |--------------------------------------------------------------------------
    |
    | Organize templates by categories for easier management
    |
    */

    'categories' => [
        'dlt_authentication' => 'DLT Authentication Templates',
        'dlt_onboarding' => 'DLT Onboarding Templates',
        'dlt_leads' => 'DLT Lead Management Templates',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Default values for common template variables
    |
    */

    'defaults' => [
        'app_name' => 'SuGanta',
        'validity' => '10',
        'response_time' => '24',
        'minutes' => '30',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Settings
    |--------------------------------------------------------------------------
    |
    | Global settings for SMS templates
    |
    */

    'settings' => [
        'max_length' => 160, // Maximum SMS length
        'allow_unicode' => true,
        'auto_replace_defaults' => true,
        'log_template_usage' => true,
    ],

];
