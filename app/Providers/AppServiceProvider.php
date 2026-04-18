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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Mailable;
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

        $this->forceAllMailsThroughQueue();

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

    /**
     * Guarantee that every outgoing email goes through
     * `php artisan queue:work` and never ships synchronously.
     *
     *   1. Override `mail.driver` resolution so that if QUEUE_CONNECTION=sync
     *      is accidentally set, a safe non-sync connection is used.
     *   2. Listen to `MessageSending` so if a non-queued Mailable ever slips
     *      through, we log a warning (easy to catch during development).
     */
    private function forceAllMailsThroughQueue(): void
    {
        // If the global queue connection is "sync", rewrite it for the
        // request lifecycle so ShouldQueue mailables still go async.
        // This project uses Redis as the primary queue driver.
        $connection = config('queue.default');
        if ($connection === 'sync') {
            $fallback = config('queue.fallback_connection', 'redis');
            config(['queue.default' => $fallback]);
        }

        Event::listen(MessageSending::class, function (MessageSending $event): void {
            // The MessageSending event fires on actual send (post-queue).
            // If we're here without a queue worker context, it's only safe
            // because ShouldQueue mailables already serialized through the
            // queue. For raw Mail::send(...) (no Mailable), we cannot queue
            // after the fact, so we just log for visibility.
            $mailable = $event->data['__laravel_mailable'] ?? null;
            if ($mailable !== null && is_string($mailable) && is_subclass_of($mailable, Mailable::class) && ! is_subclass_of($mailable, ShouldQueue::class)) {
                \Illuminate\Support\Facades\Log::warning('Mailable sent synchronously (not queued)', [
                    'mailable' => $mailable,
                ]);
            }
        });
    }
}
