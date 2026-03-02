<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token',
        'user_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'page_url',
        'ip_address',
        'user_agent',
        'started_at',
        'last_activity_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WebsiteChatMessage::class);
    }
}
