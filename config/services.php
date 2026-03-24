<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'smscountry' => [
        'auth_key' => env('SMS_COUNTRY_AUTH_KEY'),
        'auth_token' => env('SMS_COUNTRY_AUTH_TOKEN'),
        'sender_id' => env('SMS_COUNTRY_SENDER_ID'),
        'url' => env('SMS_COUNTRY_BASE_URL'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS', base_path('storage/keys/suganta-tutors-firebase-adminsdk-fbsvc-51a7fa7774.json')),
    ],

    'google' => [
        'timeout_seconds' => env('GOOGLE_API_TIMEOUT_SECONDS', 15),
        'oauth_client_json' => env('GOOGLE_OAUTH_CLIENT_JSON', 'storage/keys/suganta-sync.json'),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'oauth_token_url' => env('GOOGLE_OAUTH_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'oauth_authorize_url' => env('GOOGLE_OAUTH_AUTHORIZE_URL', 'https://accounts.google.com/o/oauth2/v2/auth'),
        'oauth_state_ttl_seconds' => env('GOOGLE_OAUTH_STATE_TTL_SECONDS', 600),
        'default_scopes' => array_values(array_filter(array_map(
            static fn (string $scope): string => trim($scope),
            explode(',', (string) env('GOOGLE_DEFAULT_SCOPES', 'https://www.googleapis.com/auth/calendar,https://www.googleapis.com/auth/drive,https://www.googleapis.com/auth/youtube.readonly,https://www.googleapis.com/auth/userinfo.email'))
        ))),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'webhook_url' => env('GOOGLE_WEBHOOK_URL'),
        'webhook_secret' => env('GOOGLE_WEBHOOK_SECRET'),
        'webhook_replay_window_seconds' => env('GOOGLE_WEBHOOK_REPLAY_WINDOW_SECONDS', 300),
        'watch_token_ttl_seconds' => env('GOOGLE_WATCH_TOKEN_TTL_SECONDS', 86400),
        'watch_renew_before_seconds' => env('GOOGLE_WATCH_RENEW_BEFORE_SECONDS', 900),
        'calendar_base_url' => env('GOOGLE_CALENDAR_BASE_URL', 'https://www.googleapis.com/calendar/v3'),
        'youtube_base_url' => env('GOOGLE_YOUTUBE_BASE_URL', 'https://www.googleapis.com/youtube/v3'),
        'drive_base_url' => env('GOOGLE_DRIVE_BASE_URL', 'https://www.googleapis.com/drive/v3'),
    ],

];
