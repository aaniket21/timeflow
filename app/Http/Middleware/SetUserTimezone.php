<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PRD §6 — SetUserTimezone middleware.
 *
 * Sets Carbon's default timezone to the authenticated user's timezone
 * for the duration of the request. This ensures all date operations
 * in controllers/services use the user's local timezone.
 *
 * Registered on BOTH web and api middleware groups.
 */
class SetUserTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->timezone) {
            // Set Carbon's default timezone for all date operations in this request
            Carbon::setLocale(config('app.locale'));
            date_default_timezone_set($user->timezone);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Reset timezone to UTC after the request to prevent leaking
        // between requests in long-lived servers (Octane/Swoole/RoadRunner)
        date_default_timezone_set('UTC');
    }
}
