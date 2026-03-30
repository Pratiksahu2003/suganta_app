<?php

use App\Http\Controllers\Api\V5\ChatbotAdminController;
use App\Http\Controllers\Api\V5\ChatbotWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chatbot API Routes (v5)
|--------------------------------------------------------------------------
|
| Webhook routes (public) + Admin management routes (auth:sanctum).
|
*/

// ── Public Webhook Routes ────────────────────────────────
Route::prefix('v5/chatbot')->group(function (): void {
    Route::get('webhook', [ChatbotWebhookController::class, 'verify']);
    Route::post('webhook', [ChatbotWebhookController::class, 'handle']);
});

// ── Admin Routes (protected) ────────────────────────────
Route::prefix('v5/chatbot/admin')
    ->middleware('auth:sanctum')
    ->group(function (): void {

        // Dashboard & Analytics
        Route::get('dashboard', [ChatbotAdminController::class, 'dashboard']);

        // Conversations
        Route::get('conversations', [ChatbotAdminController::class, 'conversations']);
        Route::get('conversations/{id}/messages', [ChatbotAdminController::class, 'messages']);
        Route::post('conversations/{id}/takeover', [ChatbotAdminController::class, 'takeover']);
        Route::post('conversations/{id}/release', [ChatbotAdminController::class, 'releaseToBot']);
        Route::post('conversations/{id}/reply', [ChatbotAdminController::class, 'sendManualReply']);

        // FAQs
        Route::get('faqs', [ChatbotAdminController::class, 'faqIndex']);
        Route::post('faqs', [ChatbotAdminController::class, 'faqStore']);
        Route::put('faqs/{id}', [ChatbotAdminController::class, 'faqUpdate']);
        Route::delete('faqs/{id}', [ChatbotAdminController::class, 'faqDestroy']);

        // Keywords
        Route::get('keywords', [ChatbotAdminController::class, 'keywordIndex']);
        Route::post('keywords', [ChatbotAdminController::class, 'keywordStore']);
        Route::put('keywords/{id}', [ChatbotAdminController::class, 'keywordUpdate']);
        Route::delete('keywords/{id}', [ChatbotAdminController::class, 'keywordDestroy']);

        // Intents
        Route::get('intents', [ChatbotAdminController::class, 'intentIndex']);
        Route::post('intents', [ChatbotAdminController::class, 'intentStore']);
        Route::put('intents/{id}', [ChatbotAdminController::class, 'intentUpdate']);
        Route::delete('intents/{id}', [ChatbotAdminController::class, 'intentDestroy']);

        // Bot Settings
        Route::get('settings', [ChatbotAdminController::class, 'settingsIndex']);
        Route::put('settings', [ChatbotAdminController::class, 'settingsUpdate']);

        // Users
        Route::get('users', [ChatbotAdminController::class, 'userIndex']);
        Route::post('users/{id}/block', [ChatbotAdminController::class, 'blockUser']);
        Route::post('users/{id}/unblock', [ChatbotAdminController::class, 'unblockUser']);

        // Leads
        Route::get('leads', [ChatbotAdminController::class, 'leads']);
        Route::put('leads/{id}/status', [ChatbotAdminController::class, 'updateLeadStatus']);
    });
