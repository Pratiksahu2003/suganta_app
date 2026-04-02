<?php

use App\Http\Controllers\Api\V6\MarketplaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace V6 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('marketplace')->middleware('auth:sanctum')->group(function () {
    // Public Marketplace (Discovery)
    Route::get('listings', [MarketplaceController::class, 'index']); // All listings
    Route::get('listings/{id}', [MarketplaceController::class, 'show']); // Detail
    Route::get('trending', [MarketplaceController::class, 'trending']); // Redis Trending
    Route::get('plans', [MarketplaceController::class, 'getPlans']); // Subscription Plans

    // Authenticated Marketplace Actions
    // Buyer Interactions
    Route::post('listings/{id}/purchase', [MarketplaceController::class, 'purchase']); // Soft Copy (Cashfree)
    Route::post('listings/{id}/contact', [MarketplaceController::class, 'contactSeller']); // Hard Copy (Chat)
    Route::get('listings/{id}/download', [MarketplaceController::class, 'download'])->name('v6.marketplace.download'); // Secure Download

    // User Management (Selling)
    Route::get('my-listings', [MarketplaceController::class, 'myListings']); // Seller Dashboard
    Route::post('my-listings', [MarketplaceController::class, 'store']); // Create
    Route::put('my-listings/{id}', [MarketplaceController::class, 'update']); // Edit
    Route::delete('my-listings/{id}', [MarketplaceController::class, 'destroy']); // Remove
});
