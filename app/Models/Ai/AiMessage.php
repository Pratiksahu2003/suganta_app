<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'ai_messages';

    protected $fillable = [
        'message_id',
        'role',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'raw_request',
        'raw_response',
    ];

    protected $casts = [
        'raw_request' => 'array',
        'raw_response' => 'array',
    ];
}

