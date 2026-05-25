<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetUserTimezone;
use App\Providers\AuthServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;
use Throwable;

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
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (! app()->environment(['local', 'testing']) && in_array($response->getStatusCode(), [500, 503, 404, 403])) {
                return Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            } elseif ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'The page expired, please try again.',
                ]);
            }
            return $response;
        });
    })->create();
