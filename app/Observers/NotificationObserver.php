<?php

namespace App\Observers;

use App\Models\Notification;
use App\Services\PushNotificationDispatcher;

class NotificationObserver
{
    public function __construct(private readonly PushNotificationDispatcher $pushNotificationDispatcher)
    {
    }

    public function created(Notification $notification): void
    {
        $this->pushNotificationDispatcher->dispatchForNotification($notification);
    }
}
