<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiUserUsage extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'ai_user_usages';

    protected $fillable = [
        'user_id',
        'total_tokens',
    ];
}

