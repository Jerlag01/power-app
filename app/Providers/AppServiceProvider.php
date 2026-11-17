<?php

namespace App\Providers;

use App\Notifications\ResetPassword;
use App\Support\Settings;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);

        // "Forgot password?" of the admin panel sends the editable template, right away.
        $this->app->bind(FilamentResetPassword::class, fn ($app, array $parameters): ResetPassword => new ResetPassword($parameters['token']));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mail settings from the admin panel override .env once the mailer is first used.
        $this->app->afterResolving('mail.manager', fn () => $this->app->make(Settings::class)->applyMailConfig());
    }
}
