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
];
