<?php

use App\Http\Controllers\Api\V4\Google\GoogleSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v4')->controller(GoogleSyncController::class)->group(function (): void {
    Route::post('google/webhook', 'webhook');
    Route::get('google/oauth/callback', 'oauthCallback');
});

Route::prefix('v4')->middleware('auth:sanctum')->group(function (): void {
    Route::prefix('google')->controller(GoogleSyncController::class)->group(function (): void {
        Route::post('connect', 'connect');
        Route::get('oauth/url', 'oauthUrl');
        Route::post('oauth/exchange-code', 'exchangeCode');
        Route::delete('disconnect', 'disconnect');
        Route::get('status', 'status');
        Route::get('urls', 'urls');
        Route::post('watch', 'startWatch');
        Route::delete('watch/{channelId}', 'stopWatch');
        Route::post('token/refresh', 'refreshToken');
        Route::post('sync', 'syncAll');
        Route::post('calendar/events', 'calendarEvents');
        Route::post('calendar/events/create', 'createCalendarEvent');
        Route::get('calendar/events/{eventId}', 'getCalendarEvent');
        Route::put('calendar/events/{eventId}', 'updateCalendarEvent');
        Route::delete('calendar/events/{eventId}', 'deleteCalendarEvent');
        Route::post('youtube/channels', 'youtubeChannels');
        Route::post('drive/files', 'driveFiles');
        Route::post('drive/files/search', 'searchDriveFiles');
        Route::post('drive/files/upload', 'uploadDriveFile');
        Route::post('drive/folders/create', 'createDriveFolder');
        Route::patch('drive/files/{fileId}/move', 'moveDriveFile');
        Route::post('drive/files/{fileId}/share', 'shareDriveFile');
        Route::patch('drive/files/{fileId}/rename', 'renameDriveFile');
        Route::delete('drive/files/{fileId}', 'deleteDriveFile');
    });
});
