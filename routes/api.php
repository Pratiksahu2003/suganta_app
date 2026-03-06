<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OptionController;
use App\Http\Controllers\Api\V1\RegistrationController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PortfolioController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\LeadController;

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

    // Contact form (public - no auth)
    Route::post('contacts', [ContactController::class, 'store']);

    // Support Ticket Routes (protected)
    Route::middleware('auth:sanctum')->prefix('support-tickets')->group(function () {
        Route::get('options', [SupportTicketController::class, 'options']);
        Route::get('/', [SupportTicketController::class, 'index']);
        Route::post('/', [SupportTicketController::class, 'store']);
        Route::get('{supportTicket}', [SupportTicketController::class, 'show']);
        Route::put('{supportTicket}', [SupportTicketController::class, 'update']);
        Route::patch('{supportTicket}', [SupportTicketController::class, 'update']);
        Route::delete('{supportTicket}', [SupportTicketController::class, 'destroy']);
        
        Route::post('{supportTicket}/reply', [SupportTicketController::class, 'reply']);
        Route::get('{supportTicket}/attachment', [SupportTicketController::class, 'downloadAttachment']);
        Route::get('{supportTicket}/replies/{reply}/attachment', [SupportTicketController::class, 'downloadReplyAttachment']);
    });

    // Payment Routes (auth user's data only)
    Route::middleware('auth:sanctum')->prefix('payments')->controller(PaymentController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('invoice/{orderId}', 'invoice');
    });

    // Portfolio Routes (auth user's data only)
    Route::middleware('auth:sanctum')
        ->prefix('portfolios')
        ->controller(PortfolioController::class)
        ->group(function () {
            Route::get('options', 'options');
            Route::get('/', 'show');
            Route::post('/', 'store');
            Route::match(['put', 'patch'], '{portfolio}', 'update');
        });

    // Lead Routes (auth user's own leads and created leads only)
    Route::middleware('auth:sanctum')->prefix('leads')->controller(LeadController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('{lead}', 'show');
    });
});
