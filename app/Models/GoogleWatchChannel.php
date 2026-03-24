<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleWatchChannel extends Model
{
    protected $fillable = [
        'user_id',
        'resource_type',
        'channel_id',
        'resource_id',
        'google_resource_uri',
        'verification_token',
        'status',
        'expires_at',
        'last_message_number',
        'last_notification_at',
        'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_notification_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
