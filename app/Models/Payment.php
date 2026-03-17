<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'reference_id',
        'user_id',
        'currency',
        'amount',
        'status',
        'meta',
        'gateway_response',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription associated with this payment
     */
    public function subscription()
    {
        return $this->hasOne(UserSubscription::class);
    }

    /**
     * Get the note purchase associated with this payment
     */
    public function notePurchase()
    {
        return $this->hasOne(NotePurchase::class);
    }
}
