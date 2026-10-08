<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
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
