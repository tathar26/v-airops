<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        \App\Models\Pirep::observe(\App\Observers\PirepObserver::class);

        if ($this->app->environment('production', 'staging') || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }
    }
}
