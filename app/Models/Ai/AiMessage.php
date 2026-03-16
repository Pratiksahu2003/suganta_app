<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'ai_messages';

    protected $fillable = [
        'ai_conversation_id',
        'user_id',
        'content',
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

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}

