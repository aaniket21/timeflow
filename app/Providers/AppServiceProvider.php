<?php

namespace App\Providers;

use App\Listeners\SyncTimezoneOnLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Gate::define('viewPulse', function (\App\Models\User $user) {
            return (bool) $user->is_admin;
        });

        // PRD §7 — Prevent N+1 queries in development.
        // Throws an exception if a lazy-loaded relationship is accessed.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Prevent silently discarding unknown attributes on fill().
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // PRD §6 — Sync browser timezone to user profile on every login.
        Event::listen(Login::class, SyncTimezoneOnLogin::class);
    }
}
