<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public function createUserNotification(
        int $userId,
        string $title,
        string $message,
        string $type = 'general',
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'normal'
    ): Notification {
        return Notification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\SystemNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $userId,
            'data' => array_merge($data, [
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'action_url' => $actionUrl,
                'priority' => $priority,
            ]),
            'read_at' => null,
        ]);
    }
}
