<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends BaseApiController
{
    /**
     * Get the authenticated user's wallet balance and transaction history.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $wallet = Wallet::getOrCreate($user->id);

        $query = $wallet->transactions()->latest();

        // Optional filters
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->string('transaction_type'));
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $transactions = $query->paginate($perPage);

        return $this->success('Wallet information retrieved successfully.', [
            'wallet' => [
                'balance' => (float) $wallet->balance,
                'total_earned' => (float) $wallet->total_earned,
                'total_withdrawn' => (float) $wallet->total_withdrawn,
                'currency' => 'INR',
            ],
            'transactions' => [
                'data' => $transactions->items(),
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
                'links' => [
                    'next' => $transactions->nextPageUrl(),
                    'prev' => $transactions->previousPageUrl(),
                ]
            ]
        ]);
    }
}
