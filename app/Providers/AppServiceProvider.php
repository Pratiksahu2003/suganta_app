<?php

namespace App\Providers;

use App\Models\Profile;
use App\Models\ProfileInstituteInfo;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Subject;
use App\Models\UserSession;
use App\Observers\NotificationObserver;
use App\Observers\ProfileInstituteInfoObserver;
use App\Observers\ProfileObserver;
use App\Observers\ReviewObserver;
use App\Observers\SubjectObserver;
use App\Observers\UserSessionObserver;
use App\Services\ModelActivityNotificationService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        Subject::observe(SubjectObserver::class);
        Profile::observe(ProfileObserver::class);
        ProfileInstituteInfo::observe(ProfileInstituteInfoObserver::class);
        Review::observe(ReviewObserver::class);
        UserSession::observe(UserSessionObserver::class);
        Notification::observe(NotificationObserver::class);

        Event::listen('eloquent.created: *', function (string $_eventName, array $payload): void {
            $model = $payload[0] ?? null;
            if (! $model instanceof \Illuminate\Database\Eloquent\Model) {
                return;
            }

            app(ModelActivityNotificationService::class)->onCreated($model);
        });

        Event::listen('eloquent.updated: *', function (string $_eventName, array $payload): void {
            $model = $payload[0] ?? null;
            if (! $model instanceof \Illuminate\Database\Eloquent\Model) {
                return;
            }

            app(ModelActivityNotificationService::class)->onUpdated($model);
        });
    }
}
