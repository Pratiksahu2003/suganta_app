<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\AiAdviserController;

Route::prefix('v2')->middleware('auth:sanctum')->group(function (): void {
    Route::prefix('ai-adviser')->controller(AiAdviserController::class)->group(function () {
        Route::post('conversations', 'start');
        Route::post('conversations/{aiConversation}/message', 'reply');
        Route::get('conversations', 'index');
        Route::get('conversations/{aiConversation}', 'show');
        Route::get('usage', 'usage');
    });
});

