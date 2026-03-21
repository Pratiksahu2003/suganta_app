<?php

use App\Http\Controllers\Api\V3\Chat\ConversationController;
use App\Http\Controllers\Api\V3\Chat\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v3')->middleware('auth:sanctum')->group(function (): void {
    Route::prefix('chat')->group(function (): void {
        Route::get('users/search', [ConversationController::class, 'searchUsers']);
        Route::get('conversations', [ConversationController::class, 'index']);
        Route::post('conversations', [ConversationController::class, 'store']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->whereNumber('conversation');
        Route::patch('conversations/{conversation}', [ConversationController::class, 'update'])->whereNumber('conversation');
        Route::post('conversations/{conversation}/read', [ConversationController::class, 'markRead'])->whereNumber('conversation');
        Route::post('conversations/{conversation}/participants', [ConversationController::class, 'addParticipant'])->whereNumber('conversation');
        Route::delete('conversations/{conversation}/participants/{user}', [ConversationController::class, 'removeParticipant'])->whereNumber(['conversation', 'user']);
        Route::post('conversations/{conversation}/leave', [ConversationController::class, 'leave'])->whereNumber('conversation');

        Route::get('conversations/{conversation}/messages', [MessageController::class, 'index'])->whereNumber('conversation');
        Route::post('conversations/{conversation}/messages', [MessageController::class, 'store'])->whereNumber('conversation');
        Route::patch('messages/{message}', [MessageController::class, 'update'])->whereNumber('message');
        Route::delete('messages/{message}', [MessageController::class, 'destroy'])->whereNumber('message');
        Route::post('messages/{message}/read', [MessageController::class, 'markRead'])->whereNumber('message');
        Route::post('messages/{message}/reaction', [MessageController::class, 'react'])->whereNumber('message');
        Route::delete('messages/{message}/reaction', [MessageController::class, 'removeReaction'])->whereNumber('message');
        Route::post('conversations/{conversation}/typing', [MessageController::class, 'typing'])->whereNumber('conversation');
    });
});
