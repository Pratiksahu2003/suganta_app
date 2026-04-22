<?php

return [
    'enabled' => env('PUSH_ENABLED', true),

    'notifications' => [
        'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', true),
        'smart_filter' => [
            // Only meaningful notifications should become push alerts.
            'enabled' => true,
            'min_priority' => 'high',
            // Prevent burst spam for the same user/type/content.
            'dedupe_seconds' => 120,
            // Additional per-user/type pacing.
            'default_rate_limit_seconds' => 180,
            'rate_limit_seconds_by_type' => [
                'message' => 30,
                'support' => 120,
                'payment' => 30,
                'booking' => 60,
                'subscription' => 120,
                'lead' => 180,
            ],
            // Local quiet hours (uses user timezone if available in preferences.timezone).
            'quiet_hours' => [
                'enabled' => true,
                'start_hour' => 22,
                'end_hour' => 7,
                'bypass_priorities' => ['urgent'],
            ],
            'always_allow_types' => [
                'payment',
                'booking',
                'support',
                'donation',
                'subscription',
                'lead',
                'warning',
                'success',
                'message',
            ],
            // Model activity pushes only for these models (extra safety layer).
            'model_push_models' => [
                \App\Models\Payment::class,
                \App\Models\SupportTicket::class,
                \App\Models\SupportTicketReply::class,
                \App\Models\UserSubscription::class,
                \App\Models\Lead::class,
            ],
        ],
    ],

    'chat' => [
        'enabled' => env('PUSH_CHAT_ENABLED', true),
        'events' => [
            'new_message' => env('PUSH_CHAT_EVENT_NEW_MESSAGE', true),
            'message_edited' => env('PUSH_CHAT_EVENT_MESSAGE_EDITED', true),
            'message_deleted' => env('PUSH_CHAT_EVENT_MESSAGE_DELETED', true),
            'message_read' => env('PUSH_CHAT_EVENT_MESSAGE_READ', false),
            'reaction_added' => env('PUSH_CHAT_EVENT_REACTION_ADDED', true),
            'reaction_removed' => env('PUSH_CHAT_EVENT_REACTION_REMOVED', true),
        ],
    ],

    'model_activity' => [
        'enabled' => env('PUSH_MODEL_ACTIVITY_ENABLED', true),
        'send_to_all' => env('PUSH_MODEL_ACTIVITY_SEND_TO_ALL', true),
        'cooldown_seconds' => (int) env('PUSH_MODEL_ACTIVITY_COOLDOWN_SECONDS', 120),
        'roles' => array_values(array_filter(array_map(
            static fn (string $role): string => trim($role),
            explode(',', (string) env('PUSH_MODEL_ACTIVITY_ROLES', 'admin,super-admin'))
        ))),
        // Only these model events are considered for broadcast-worthy activity.
        // NOTE: All payment-related models (Payment, WalletTransaction,
        // UserSubscription, SubscriptionPlan) are ignored globally in
        // `ignored_models` below, so they never reach these lists at runtime.
        'important_models' => [
            'created' => [
                \App\Models\SupportTicket::class,
                \App\Models\SupportTicketReply::class,
                // Profile / wallet / portfolio models - always notify on creation.
                \App\Models\Profile::class,
                \App\Models\ProfileTeachingInfo::class,
                \App\Models\ProfileStudentInfo::class,
                \App\Models\ProfileInstituteInfo::class,
                \App\Models\Portfolio::class,
                \App\Models\Wallet::class,
            ],
            'updated' => [
                \App\Models\SupportTicket::class,
                \App\Models\SupportTicketReply::class,
                \App\Models\Lead::class,
                // Profile / wallet / portfolio models - always notify on update.
                \App\Models\Profile::class,
                \App\Models\ProfileTeachingInfo::class,
                \App\Models\ProfileStudentInfo::class,
                \App\Models\ProfileInstituteInfo::class,
                \App\Models\Portfolio::class,
                \App\Models\Wallet::class,
            ],
        ],
        // Models that MUST receive a security email on both create & update,
        // regardless of the `email_on_all_creations` / `email_on_all_updates` flags.
        // Payment-related models are intentionally excluded — see `ignored_models`.
        'email_force_models' => [
            \App\Models\Profile::class,
            \App\Models\ProfileTeachingInfo::class,
            \App\Models\ProfileStudentInfo::class,
            \App\Models\ProfileInstituteInfo::class,
            \App\Models\Portfolio::class,
            \App\Models\Wallet::class,
        ],
        // For updates, at least one field in this list must change.
        // Payment-related model entries are omitted — those models are ignored.
        'important_update_fields' => [
            '*' => ['status', 'state', 'verification_status', 'is_active', 'approved_at', 'rejected_at', 'closed_at', 'expires_at', 'amount'],
            \App\Models\Booking::class => ['status', 'scheduled_at', 'cancelled_at'],
            \App\Models\SupportTicket::class => ['status', 'priority', 'assigned_to', 'resolved_at', 'closed_at'],
            \App\Models\Lead::class => ['status', 'assigned_to', 'priority'],
        ],
        // Fields that are purely system / token / session / bookkeeping noise.
        // Any change to ONLY these fields will not trigger an email or notification.
        'ignored_fields' => [
            // Framework timestamps & soft-deletes
            'updated_at',
            'created_at',
            'deleted_at',
            // Common Laravel verification columns (explicitly excluded per product requirement)
            'email_verified_at',
            'phone_verified_at',
            'mobile_verified_at',
            'verified_at',
            'email_verification_sent_at',
            'phone_verification_sent_at',
            // Session / activity tracking
            'last_activity',
            'last_seen_at',
            'last_login_at',
            'last_logout_at',
            'login_at',
            'logout_at',
            'last_login_ip',
            'last_login_user_agent',
            'failed_login_attempts',
            'locked_at',
            'seen_at',
            'read_at',
            'is_current_session',
            'session_id',
            'ip_address',
            'user_agent',
            // Tokens / secrets (NEVER expose or alert on these)
            'remember_token',
            'api_token',
            'access_token',
            'refresh_token',
            'token',
            'token_hash',
            'verification_token',
            'email_verification_token',
            'reset_token',
            'password_reset_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'two_factor_confirmed_at',
            'fcm_token',
            'device_token',
            'push_token',
            'google_access_token',
            'google_refresh_token',
            'google_token_expires_at',
            'facebook_access_token',
            'apple_access_token',
            // Password-related
            'password',
            'password_hash',
        ],
        'model_ignored_fields' => [
            \App\Models\User::class => [
                'last_login_at',
                'remember_token',
                'google_access_token',
                'google_refresh_token',
                'google_token_expires_at',
                'fcm_token',
                'device_token',
                'api_token',
            ],
            \App\Models\UserSession::class => ['last_activity'],
        ],
        // System / token / session / log models - no emails or pushes for these.
        'ignored_models' => [
            \App\Models\Notification::class,
            \App\Models\UserSession::class,
            \App\Models\Otp::class,
            \App\Models\GoogleWatchChannel::class,
            // Website / public chat models (they live outside App\Models\Chat
            // so the namespace filter below doesn't cover them).
            \App\Models\WebsiteChatMessage::class,
            \App\Models\WebsiteChatSession::class,
            // Payment-related models - never produce pushes or security emails.
            \App\Models\Payment::class,
            \App\Models\WalletTransaction::class,
            \App\Models\UserSubscription::class,
            \App\Models\SubscriptionPlan::class,
            // Common framework/package token & session models (string-form so absence of the class is safe)
            'Laravel\\Sanctum\\PersonalAccessToken',
            'Laravel\\Passport\\Token',
            'Laravel\\Passport\\RefreshToken',
            'Laravel\\Passport\\AuthCode',
            'Laravel\\Passport\\Client',
        ],
        // Namespace-prefix ignore list. ANY model under these namespaces is
        // skipped — no pushes, no security emails. Useful for large model
        // families (Chat, Chatbot, AI) so we don't have to list every class.
        'ignored_model_namespaces' => [
            'App\\Models\\Chat',
            'App\\Models\\Chatbot',
            'App\\Models\\Ai',
        ],
        // When true, the queued security email fires on ANY non-ignored model update,
        // not just the "important" ones configured above.
        'email_on_all_updates' => env('PUSH_MODEL_ACTIVITY_EMAIL_ON_ALL_UPDATES', true),
        // When true, the queued security email fires on ANY non-ignored model creation,
        // not just "important" ones / models in `email_force_models`.
        'email_on_all_creations' => env('PUSH_MODEL_ACTIVITY_EMAIL_ON_ALL_CREATIONS', false),
        // Queue connection for security-alert mail. MUST NOT be "sync" so that
        // emails are only delivered by a running `php artisan queue:work`.
        // Defaults to "redis"; override to "database" or another driver in .env.
        'mail_queue_connection' => env('PUSH_MODEL_ACTIVITY_MAIL_CONNECTION', 'redis'),
        'mail_queue_name' => env('PUSH_MODEL_ACTIVITY_MAIL_QUEUE', 'default'),
    ],
];
