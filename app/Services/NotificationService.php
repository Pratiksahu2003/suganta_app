<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(private readonly FirebasePushService $firebasePushService)
    {
    }

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
        Log::channel('firebase_push')->info('notification.create.started', [
            'user_id' => $userId,
            'type' => $type,
            'priority' => $priority,
            'has_action_url' => $actionUrl !== null,
        ]);

        $notification = Notification::create([
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

        $user = User::query()->find($userId);
        if ($user !== null) {
            Log::channel('firebase_push')->info('notification.push.triggered', [
                'user_id' => $userId,
                'notification_id' => (string) $notification->id,
                'type' => $type,
            ]);
            $this->firebasePushService->sendToUser($user, $title, $message, [
                'kind' => 'system_notification',
                'notification_id' => (string) $notification->id,
                'type' => $type,
                'priority' => $priority,
                'action_url' => $actionUrl,
            ]);
        } else {
            Log::channel('firebase_push')->warning('notification.push.skipped.user_not_found', [
                'user_id' => $userId,
                'notification_id' => (string) $notification->id,
            ]);
        }

        return $notification;
    }

    /**
     * Create notifications for all users in one or more roles.
     *
     * Note: Users can have a primary `role` column and/or many-to-many `roles()`.
     *
     * @return array<int, Notification> Created notifications
     */
    public function createRoleNotification(
        string|array $roles,
        string $title,
        string $message,
        string $type = 'general',
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'normal'
    ): array {
        $roles = is_array($roles) ? array_values(array_filter($roles)) : [$roles];
        $roles = array_values(array_unique(array_map('strval', $roles)));

        if (count($roles) === 0) {
            return [];
        }

        $userIds = User::query()
            ->whereIn('role', $roles)
            ->orWhereHas('roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);
            })
            ->pluck('id');

        $created = [];
        foreach ($userIds as $userId) {
            $created[] = $this->createUserNotification(
                (int) $userId,
                $title,
                $message,
                $type,
                $data,
                $actionUrl,
                $priority
            );
        }

        return $created;
    }
}
