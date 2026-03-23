<?php

return [
    'enabled' => env('PUSH_ENABLED', true),

    'notifications' => [
        'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', true),
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
        'roles' => array_values(array_filter(array_map(
            static fn (string $role): string => trim($role),
            explode(',', (string) env('PUSH_MODEL_ACTIVITY_ROLES', 'admin,super-admin'))
        ))),
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
