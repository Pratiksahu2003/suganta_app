<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'note_id',
        'payment_id',
        'amount',
        'status',
        'download_count',
        'purchased_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'download_count' => 'integer',
        'purchased_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function canDownload(): bool
    {
        return $this->status === 'completed';
    }
}
