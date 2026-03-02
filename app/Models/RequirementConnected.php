<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementConnected extends Model
{
    use HasFactory;

    protected $table = 'requirement_connected';

    protected $fillable = [
        'requirement_id',
        'user_id',
        'status',
        'message',
        'connected_at',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(StudyRequirement::class, 'requirement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
