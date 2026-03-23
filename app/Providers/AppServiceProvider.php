<?php

namespace App\Providers;

use App\Models\Profile;
use App\Models\ProfileInstituteInfo;
use App\Models\Review;
use App\Models\Subject;
use App\Models\UserSession;
use App\Observers\ProfileInstituteInfoObserver;
use App\Observers\ProfileObserver;
use App\Observers\ReviewObserver;
use App\Observers\SubjectObserver;
use App\Observers\UserSessionObserver;
use Illuminate\Support\Facades\Broadcast;
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
    }
}
