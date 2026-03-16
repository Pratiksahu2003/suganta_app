<?php

return [
    'api_key' => env('GEMINI_API_KEY'),
    'model_id' => env('GEMINI_MODEL_ID', 'gemini-2.5-flash-lite'),

    // Optional: dedicated AI system user in main DB users table
    'system_user_id' => env('AI_ADVISER_SYSTEM_USER_ID'),

    // Free tier token allowance before requiring subscription (per user)
    'free_token_limit' => (int) env('AI_FREE_TOKENS', 100000),

    // Subscription type id for AI plans (Basic / Pro / Advance)
    'subscription_type' => (int) env('AI_SUBSCRIPTION_TYPE', 3),

    // Maximum number of previous messages to send as history context.
    'history_limit' => (int) env('GEMINI_HISTORY_LIMIT', 10),

    // Maximum characters per history message sent to the model.
    'history_message_max_chars' => (int) env('GEMINI_HISTORY_MESSAGE_MAX_CHARS', 800),

    // Maximum completion length in tokens for each AI response.
    'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 500),
];

