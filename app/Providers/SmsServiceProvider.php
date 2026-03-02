<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SmsCountryService;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('smscountry', function ($app) {
            return new SmsCountryService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
