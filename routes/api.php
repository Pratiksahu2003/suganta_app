<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OptionController;
use App\Http\Controllers\Api\V1\RegistrationController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api and use the 'api' middleware group
| (throttling, SubstituteBindings). Use version prefixes (e.g. v1) for
| backward-compatible API versioning.
|
*/

Route::prefix('v1')->group(function (): void {
    // Auth Routes
    Route::prefix('auth')->group(function () {
        // Public Auth Routes (AuthController)
        Route::controller(AuthController::class)->group(function () {
            Route::post('register', 'register');
            Route::post('login', 'login');
            Route::post('login/send-otp', 'sendLoginOtp');
            Route::post('login/verify', 'verifyLogin');
            Route::post('forgot-password', 'forgotPassword');
            Route::post('reset-password', 'resetPassword');
        });

        // Protected Auth Routes
        Route::middleware('auth:sanctum')->group(function () {
            // AuthController Protected Routes
            Route::controller(AuthController::class)->group(function () {
                Route::post('logout', 'logout');
                Route::post('logout-all', 'logoutFromAllDevices');
                Route::post('refresh-token', 'refreshToken');
            });

            // Verification Routes (VerificationController)
            Route::controller(VerificationController::class)->prefix('verification')->group(function () {
                Route::post('resend', 'resend');
                Route::post('verify', 'verify');
            });
        });
    });

    Route::get('options', [OptionController::class, 'index']);
    Route::get('registration/charges', [RegistrationController::class, 'charges']);
});
