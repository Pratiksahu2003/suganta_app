<?php

namespace App\Traits;

use App\Services\ActivityNotificationService;
use Illuminate\Support\Facades\App;

trait HasActivityNotifications
{
    /**
     * Boot the trait
     */
    protected static function bootHasActivityNotifications()
    {
        // Session created
        static::created(function ($model) {
            $model->triggerCreatedNotification();
        });

        // Session updated
        static::updated(function ($model) {
            $model->triggerUpdatedNotification();
        });

        // Session deleted
        static::deleted(function ($model) {
            $model->triggerDeletedNotification();
        });
    }

    /**
     * Trigger notification when model is created
     */
    protected function triggerCreatedNotification()
    {
        $notificationService = App::make(ActivityNotificationService::class);
        
        switch (get_class($this)) {
            case 'App\Models\Session':
                $notificationService->sessionCreated($this);
                break;
            case 'App\Models\SupportTicket':
                $notificationService->supportTicketCreated($this);
                break;
            case 'App\Models\Payment':
                if ($this->status === 'completed') {
                    $notificationService->paymentSuccessful($this);
                } elseif ($this->status === 'failed') {
                    $notificationService->paymentFailed($this);
                }
                break;
            case 'App\Models\Review':
                $notificationService->newReview($this);
                break;
        }
    }

    /**
     * Trigger notification when model is updated
     */
    protected function triggerUpdatedNotification()
    {
        $notificationService = App::make(ActivityNotificationService::class);
        
        // Get changes
        $changes = $this->getChanges();
        $original = $this->getOriginal();
        
        // Filter out timestamps and other non-relevant changes
        $relevantChanges = array_intersect_key($changes, array_flip([
            'title', 'status', 'scheduled_at', 'priority', 'assigned_to', 'amount', 'currency'
        ]));
        
        if (empty($relevantChanges)) {
            return;
        }
        
        switch (get_class($this)) {
            case 'App\Models\Session':
                $notificationService->sessionUpdated($this, $relevantChanges);
                break;
            case 'App\Models\SupportTicket':
                $notificationService->supportTicketUpdated($this, $relevantChanges);
                break;
            case 'App\Models\Payment':
                if (isset($changes['status'])) {
                    if ($changes['status'] === 'completed') {
                        $notificationService->paymentSuccessful($this);
                    } elseif ($changes['status'] === 'failed') {
                        $notificationService->paymentFailed($this);
                    }
                }
                break;
        }
    }

    /**
     * Trigger notification when model is deleted
     */
    protected function triggerDeletedNotification()
    {
        $notificationService = App::make(ActivityNotificationService::class);
        
        switch (get_class($this)) {
            case 'App\Models\Session':
                $notificationService->sessionCancelled($this, 'Session was deleted');
                break;
        }
    }

    /**
     * Manually trigger a specific notification
     */
    public function triggerNotification($method, ...$args)
    {
        $notificationService = App::make(ActivityNotificationService::class);
        
        if (method_exists($notificationService, $method)) {
            return $notificationService->$method($this, ...$args);
        }
        
        return null;
    }
} 