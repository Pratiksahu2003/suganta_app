<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta Graph API Configuration
    |--------------------------------------------------------------------------
    */

    'meta_app_id' => env('META_APP_ID'),
    'meta_app_secret' => env('META_APP_SECRET'),
    'meta_page_token' => env('META_PAGE_ACCESS_TOKEN'),
    'meta_ig_page_token' => env('META_IG_PAGE_ACCESS_TOKEN'),
    'meta_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN', 'suganta_chatbot_verify_2026'),
    'meta_api_version' => env('META_API_VERSION', 'v19.0'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Reply Configuration
    |--------------------------------------------------------------------------
    */

    'fallback_message' => env(
        'CHATBOT_FALLBACK_MESSAGE',
        'Thanks for reaching out to SuGanta! 🎓 Our team will get back to you shortly. In the meantime, you can visit suganta.in for more information.'
    ),

    'welcome_message' => env(
        'CHATBOT_WELCOME_MESSAGE',
        'Welcome to SuGanta! 🎓 I\'m here to help you find the best tutors, courses, and educational opportunities. How can I assist you today?'
    ),

    /*
    |--------------------------------------------------------------------------
    | AI Fallback Provider (gemini or grok)
    |--------------------------------------------------------------------------
    | Uses the existing GeminiAiService or GrokAiService already configured
    | in the project. No new API keys needed.
    |
    */

    'ai_provider' => env('CHATBOT_AI_PROVIDER', 'gemini'),

    'ai_system_prompt' => env(
        'CHATBOT_AI_SYSTEM_PROMPT',
        'You are SuGanta\'s official AI assistant chatbot responding to Instagram/Messenger DMs. '
        . 'SuGanta (suganta.com) is India\'s premier education platform connecting students with top tutors, '
        . 'coaching institutes, and global learning opportunities. '
        . "\n\nKEY INFORMATION ABOUT SUGANTA:\n"
        . '• Students can find tutors by subject, exam, city, or area\n'
        . '• Tutors offer both online (video call) and offline (home tuition) sessions\n'
        . '• Pricing varies by subject and tutor (₹200–2000/session typical range)\n'
        . '• Demo/trial sessions are available from most tutors for free\n'
        . '• Subjects: Maths, Science, English, Hindi, Coding, JEE, NEET, UPSC, Music, Art & more\n'
        . '• Payments: UPI, Cards, Net Banking via Cashfree (secure)\n'
        . '• Students can also buy study notes, access courses, and read educational blogs\n'
        . '• Teachers can register at suganta.in to start teaching and earn\n'
        . '• Support email: support@suganta.co\n'
        . '• Website: suganta.com | App: Search "SuGanta" on Play Store/App Store\n'
        . "\n\nRULES:\n"
        . '1. Keep replies under 300 characters (Instagram/Messenger friendly)\n'
        . '2. Be friendly, use emojis, and sound human\n'
        . '3. Always guide users to suganta.com for detailed actions\n'
        . '4. If asked in Hindi/Hinglish, reply in the same language\n'
        . '5. Never make up specific prices or guarantees\n'
        . '6. For complaints, empathize and direct to support@suganta.co\n'
        . '7. For booking/enrollment, guide to suganta.com step by step\n'
        . '8. Always end with a question or call-to-action to keep conversation going'
    ),

    /*
    |--------------------------------------------------------------------------
    | Performance & Caching
    |--------------------------------------------------------------------------
    */

    'response_timeout_ms' => (int) env('CHATBOT_RESPONSE_TIMEOUT', 2000),
    'cache_ttl' => (int) env('CHATBOT_CACHE_TTL', 3600), // 1 hour
    'cache_prefix' => 'chatbot:',

    /*
    |--------------------------------------------------------------------------
    | Lead Capture
    |--------------------------------------------------------------------------
    | Intents that trigger automatic lead capture.
    |
    */

    'lead_capture_intents' => [
        'enrollment_interest',
        'pricing_query',
        'demo_request',
        'teacher_search',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'webhook_rate_limit' => (int) env('CHATBOT_WEBHOOK_RATE_LIMIT', 120), // per minute

];
