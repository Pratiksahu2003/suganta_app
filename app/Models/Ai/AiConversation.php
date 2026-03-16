<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'ai_conversations';

    protected $fillable = [
        'user_id',
        'subject',
        'status',
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

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'ai_conversation_id')->orderBy('created_at');
    }
}

