<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetUserTimezone;
use App\Providers\AuthServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AuthServiceProvider::class,
        FortifyServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // PRD §6 — SetUserTimezone registered on BOTH web and api groups
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
            SetUserTimezone::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            SetUserTimezone::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
