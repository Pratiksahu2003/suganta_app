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
                \App\Models\Booking::class,
                \App\Models\SupportTicket::class,
                \App\Models\SupportTicketReply::class,
                \App\Models\Donation::class,
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
        'important_models' => [
            'created' => [
                \App\Models\Payment::class,
                \App\Models\Booking::class,
                \App\Models\SupportTicket::class,
                \App\Models\SupportTicketReply::class,
                \App\Models\Donation::class,
                \App\Models\UserSubscription::class,
            ],
            'updated' => [
                \App\Models\Payment::class,
                \App\Models\Booking::class,
                \App\Models\SupportTicket::class,
                \App\Models\SupportTicketReply::class,
                \App\Models\Donation::class,
                \App\Models\UserSubscription::class,
                \App\Models\Lead::class,
            ],
        ],
        // For updates, at least one field in this list must change.
        'important_update_fields' => [
            '*' => ['status', 'state', 'verification_status', 'is_active', 'approved_at', 'rejected_at', 'closed_at', 'expires_at', 'amount'],
            \App\Models\Payment::class => ['status', 'amount', 'paid_at'],
            \App\Models\Booking::class => ['status', 'scheduled_at', 'cancelled_at'],
            \App\Models\SupportTicket::class => ['status', 'priority', 'assigned_to', 'resolved_at', 'closed_at'],
            \App\Models\UserSubscription::class => ['status', 'expires_at', 'started_at'],
            \App\Models\Lead::class => ['status', 'assigned_to', 'priority'],
        ],
        'ignored_fields' => [
            'updated_at',
            'last_activity',
            'last_seen_at',
            'seen_at',
            'read_at',
            'login_at',
            'logout_at',
            'is_current_session',
        ],
        'model_ignored_fields' => [
            \App\Models\User::class => ['last_login_at', 'google_access_token', 'google_refresh_token', 'google_token_expires_at'],
            \App\Models\UserSession::class => ['last_activity'],
        ],
        'ignored_models' => [
            \App\Models\Notification::class,
            \App\Models\UserSession::class,
            \App\Models\Chat\ChatMessageRead::class,
            \App\Models\Chat\ChatMessageReaction::class,
            \App\Models\WebsiteChatMessage::class,
            \App\Models\WebsiteChatSession::class,
        ],
    ],
];
