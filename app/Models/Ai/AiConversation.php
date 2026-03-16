<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'ai_conversations';

    protected $fillable = [
        'conversation_id',
        'model',
        'purpose',
        'settings',
        'total_prompt_tokens',
        'total_completion_tokens',
        'total_tokens',
        'last_used_at',
        'last_error_code',
        'last_error_message',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_used_at' => 'datetime',
    ];
}

