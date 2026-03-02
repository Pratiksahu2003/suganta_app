<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\DatabaseNotification;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Notification extends DatabaseNotification
{
    use HasFactory;

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime'
    ];

    /**
     * Get the user that owns the notification
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for recent notifications (last 30 days)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->whereJsonContains('data->priority', 'high');
    }

    /**
     * Check if notification is read
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Get the time ago for the notification
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the notification title
     */
    public function getTitleAttribute()
    {
        return $this->data['title'] ?? 'Notification';
    }

    /**
     * Get the notification message
     */
    public function getMessageAttribute()
    {
        return $this->data['message'] ?? '';
    }

    /**
     * Get the notification priority
     */
    public function getPriorityAttribute()
    {
        return $this->data['priority'] ?? 'normal';
    }

    /**
     * Get the notification action URL
     */
    public function getActionUrlAttribute()
    {
        return $this->data['action_url'] ?? null;
    }

    /**
     * Get the notification icon based on type
     */
    public function getIconAttribute()
    {
        $icons = [
            'session' => 'bi-calendar-event',
            'student' => 'bi-person',
            'message' => 'bi-chat',
            'review' => 'bi-star',
            'payment' => 'bi-credit-card',
            'system' => 'bi-gear',
            'warning' => 'bi-exclamation-triangle',
            'success' => 'bi-check-circle',
            'info' => 'bi-info-circle',
            'support' => 'bi-headset'
        ];

        return $icons[$this->data['type'] ?? 'system'] ?? 'bi-bell';
    }

    /**
     * Get the notification color based on type
     */
    public function getColorAttribute()
    {
        $colors = [
            'session' => 'primary',
            'student' => 'success',
            'message' => 'info',
            'review' => 'warning',
            'payment' => 'success',
            'system' => 'secondary',
            'warning' => 'warning',
            'success' => 'success',
            'info' => 'info',
            'support' => 'info'
        ];

        return $colors[$this->data['type'] ?? 'system'] ?? 'primary';
    }

    /**
     * Check if notification is expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Create a new notification
     */
    public static function createNotification($userId, $title, $message, $type = 'system', $data = [], $actionUrl = null, $priority = 'normal')
    {
        $notificationData = array_merge($data, [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl,
            'priority' => $priority
        ]);

        return self::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\CustomNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $userId,
            'data' => $notificationData
        ]);
    }
} 