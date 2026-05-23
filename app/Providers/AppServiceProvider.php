<?php

namespace App\Providers;

use App\Listeners\SyncTimezoneOnLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
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
        // PRD §7 — Prevent N+1 queries in development.
        // Throws an exception if a lazy-loaded relationship is accessed.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Prevent silently discarding unknown attributes on fill().
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // PRD §6 — Sync browser timezone to user profile on every login.
        Event::listen(Login::class, SyncTimezoneOnLogin::class);
    }
}
