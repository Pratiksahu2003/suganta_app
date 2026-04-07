<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('google:watches-renew')
    ->everySixHours($minutes = 0)
    ->withoutOverlapping()
    ->onOneServer()
    ->name('google-watch-renewal');

Schedule::command('subscription:check-expiry')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('subscription-expiry-check');
