<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\AiAdviserController;

Route::prefix('v2')->middleware('auth:sanctum')->group(function (): void {
    Route::prefix('ai-adviser')->controller(AiAdviserController::class)->group(function () {
        Route::post('conversations', 'start');
        Route::post('conversations/{conversation}/message', 'reply');
        Route::get('conversations', 'index');
        Route::get('conversations/{conversation}', 'show');
    });
});

