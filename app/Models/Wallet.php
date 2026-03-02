<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_withdrawn',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
    ];

    /**
     * Get the user that owns this wallet
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Credit amount to wallet
     */
    public function credit(float $amount, string $transactionType, $referenceId = null, string $referenceType = null, string $description = null, array $meta = [])
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->total_earned += $amount;
        $this->save();

        // Create transaction record
        WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'credit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'transaction_type' => $transactionType,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'description' => $description,
            'meta' => $meta,
        ]);

        return $this;
    }

    /**
     * Debit amount from wallet
     */
    public function debit(float $amount, string $transactionType, $referenceId = null, string $referenceType = null, string $description = null, array $meta = [])
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->total_withdrawn += $amount;
        $this->save();

        // Create transaction record
        WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'transaction_type' => $transactionType,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'description' => $description,
            'meta' => $meta,
        ]);

        return $this;
    }

    /**
     * Get or create wallet for a user
     */
    public static function getOrCreate(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'balance' => 0.00,
                'total_earned' => 0.00,
                'total_withdrawn' => 0.00,
            ]
        );
    }
}
