<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\AiAdviserController;
use App\Http\Controllers\Api\V2\NoteController;
use App\Http\Controllers\Api\V2\ReviewController;

Route::prefix('v2')->middleware('auth:sanctum')->group(function (): void {
    Route::prefix('notes')->controller(NoteController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('categories', 'categories');
        Route::get('types', 'types');
        Route::get('my-purchases', 'myPurchases');
        Route::post('purchase', 'purchase');
        Route::get('{note}/check-access', 'checkAccess')->whereNumber('note');
        Route::get('{note}/download', 'download')->whereNumber('note');
        Route::get('{note}', 'show')->whereNumber('note');
    });

    Route::prefix('ai-adviser')->controller(AiAdviserController::class)->group(function () {
        Route::post('conversations', 'start');
        Route::post('conversations/{aiConversation}/message', 'reply');
        Route::get('conversations', 'index');
        Route::get('conversations/{aiConversation}', 'show');
        Route::get('usage', 'usage');
    });

    Route::prefix('reviews')->controller(ReviewController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('my', 'myReviews');
        Route::get('stats', 'stats');
        Route::get('check', 'check');
        Route::post('/', 'store');
        Route::get('{review}', 'show')->whereNumber('review');
        Route::match(['put', 'patch'], '{review}', 'update')->whereNumber('review');
        Route::delete('{review}', 'destroy')->whereNumber('review');
        Route::post('{review}/helpful', 'markHelpful')->whereNumber('review');
        Route::post('{review}/reply', 'reply')->whereNumber('review');
        Route::post('{review}/report', 'report')->whereNumber('review');
    });
});

Route::get('v2/reviews/stats', [ReviewController::class, 'stats']);
Route::get('v2/reviews/list', [ReviewController::class, 'index']);
