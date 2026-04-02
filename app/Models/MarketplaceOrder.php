<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'listing_id',
        'amount',
        'commission_amount',
        'seller_amount',
        'payment_id',
        'status',
        'download_token',
        'download_expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'seller_amount' => 'decimal:2',
        'download_expires_at' => 'datetime',
    ];

    /**
     * Get the buyer
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the listing
     */
    public function listing()
    {
        return $this->belongsTo(MarketplaceListing::class);
    }

    /**
     * Scope for completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
