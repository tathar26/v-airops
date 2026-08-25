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

        // Global Gate Override for System Administrators
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
                return true;
            }
        });

        // Blade Directives for Airline RBAC
        \Illuminate\Support\Facades\Blade::if('airlineCan', function (string $permission, $airline = null) {
            return auth()->check() && auth()->user()->hasAirlinePermission($permission, $airline);
        });

        \Illuminate\Support\Facades\Blade::if('systemAdmin', function () {
            return auth()->check() && auth()->user()->isSystemAdmin();
        });

        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function ($notifiable, $token) {
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Reset Your V-Air Ops Password')
                ->view('emails.reset-password', [
                    'url' => $resetUrl,
                    'user' => $notifiable,
                    'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
                ]);
        });
    }
}
