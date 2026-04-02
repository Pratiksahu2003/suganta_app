<?php

use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V6\MarketplaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace V6 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('marketplace')->middleware('auth:sanctum')->group(function () {
    // Public Marketplace (Discovery)
    Route::get('listings', [MarketplaceController::class, 'index'])->name('v6.marketplace.index');
    Route::get('listings/{id}', [MarketplaceController::class, 'show'])->name('v6.marketplace.show');
    Route::get('trending', [MarketplaceController::class, 'trending'])->name('v6.marketplace.trending');
    Route::get('plans', [MarketplaceController::class, 'getPlans'])->name('v6.marketplace.plans');

    // Authenticated Marketplace Actions
    // Buyer Interactions
    Route::post('listings/{id}/purchase', [MarketplaceController::class, 'purchase']); // Soft Copy (Cashfree)
    Route::post('listings/{id}/contact', [MarketplaceController::class, 'contactSeller']); // Hard Copy (Chat)
    Route::get('listings/{id}/download', [MarketplaceController::class, 'download'])->name('v6.marketplace.download'); // Secure Download

    // User Management (Selling)
    Route::get('my-listings', [MarketplaceController::class, 'myListings'])->name('v6.marketplace.my-listings');
    Route::get('my-purchases', [MarketplaceController::class, 'myPurchases'])->name('v6.marketplace.my-purchases');
    Route::post('my-listings', [MarketplaceController::class, 'store'])->name('v6.marketplace.store');
    Route::put('my-listings/{id}', [MarketplaceController::class, 'update']); // Edit
    Route::delete('my-listings/{id}', [MarketplaceController::class, 'destroy']); // Remove
});

// Wallet Routes
Route::middleware('auth:sanctum')->prefix('wallet')->controller(WalletController::class)->group(function () {
    Route::get('/', 'index');
});
