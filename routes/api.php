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
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\StudyRequirementController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PublicTeacherController;
use App\Http\Controllers\Api\V1\PublicInstituteController;

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
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('subjects', [SubjectController::class, 'index']);

    // Public Teacher List & Profile (no auth)
    Route::prefix('teachers')->controller(PublicTeacherController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('{idOrSlug}', 'show');
    });

    // Public Institute List & Profile (no auth)
    Route::prefix('institutes')->controller(PublicInstituteController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('{idOrSlug}', 'show');
    });

    // Dashboard (auth only)
    Route::middleware('auth:sanctum')->get('dashboard', [DashboardController::class, 'index']);

    // Notifications (auth user's notifications with pagination)
    Route::middleware('auth:sanctum')->get('notifications', [NotificationController::class, 'index']);

    // Admin Dashboard (admin only)

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

    // Payment — public routes (Cashfree callbacks / webhooks / checkout proxy, no auth required)
    Route::controller(PaymentController::class)->prefix('payment')->group(function () {
        Route::get('checkout', 'checkout');   // proxy: always gives a fresh Cashfree redirect
        Route::get('callback', 'callback');   // Cashfree return URL after checkout
        Route::post('webhook', 'webhook');
    });

    // Payment Routes (auth user's data only)
    Route::middleware('auth:sanctum')->prefix('payments')->controller(PaymentController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('invoice/{orderId}', 'invoice');
    });

    // Portfolio Routes (one portfolio per user only)
    Route::middleware('auth:sanctum')
        ->prefix('portfolios')
        ->controller(PortfolioController::class)
        ->group(function () {
            Route::get('options', 'options');
            Route::get('/', 'show');
            Route::post('/', 'store');
            Route::post( '/update', 'update');
        });

    // Lead Routes (auth user's own leads and created leads only)
    Route::middleware('auth:sanctum')->prefix('leads')->controller(LeadController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('{lead}', 'show');
    });

    // Study Requirement Routes (list, create, show, connect, my connections)
    Route::middleware('auth:sanctum')
        ->prefix('study-requirements')
        ->controller(StudyRequirementController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('my-connections', 'myConnections');
            Route::get('{studyRequirement}', 'show')->whereNumber('studyRequirement');
            Route::post('{studyRequirement}/connect', 'connect')->whereNumber('studyRequirement');
        });

    // Subscription Routes
    Route::prefix('subscriptions')->controller(SubscriptionController::class)->group(function () {
        // Public routes - subscription plans
        Route::get('plans', 'plans');
        Route::get('plans/{plan}', 'plan');
        
        // Protected routes - user subscriptions and purchases
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('my-subscriptions', 'mySubscriptions');
            Route::get('current', 'currentSubscription');
            Route::post('purchase', 'purchase');
            Route::patch('{subscription}/cancel', 'cancel');
            Route::post('{subscription}/renew', 'renew');
        });
    });

    // Profile Routes (auth user's own profile only)
    Route::middleware('auth:sanctum')->prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('form-autofill', 'formAutofill');
        Route::match(['put', 'patch'], '/', 'update');
        Route::match(['put', 'patch'], 'location', 'updateLocation');
        Route::match(['put', 'patch'], 'social', 'updateSocial');
        Route::match(['put', 'patch'], 'teaching', 'updateTeaching');
        Route::match(['put', 'patch'], 'institute', 'updateInstitute');
        Route::match(['put', 'patch'], 'student', 'updateStudent');
        Route::match(['put', 'post'], 'avatar', 'updateAvatar');
        Route::match(['put', 'patch'], 'password', 'updatePassword');
        Route::match(['put', 'patch'], 'preferences', 'updatePreferences');
        Route::get('completion', 'completion');
        Route::post('refresh', 'refresh');
        Route::post('cache/clear', 'clearCache');
        Route::delete('/', 'destroy');
    });
});
