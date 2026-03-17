<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\AiAdviserController;
use App\Http\Controllers\Api\V2\ReviewController;

Route::prefix('v2')->middleware('auth:sanctum')->group(function (): void {
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

