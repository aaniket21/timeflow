<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * PRD §6 — Auto-detect timezone from browser on login.
 *
 * Updates the user's timezone profile field with the browser-detected
 * timezone sent in the login request. This ensures timezone stays
 * current even if the user changes location.
 */
class SyncTimezoneOnLogin
{
    public function __construct(
        private readonly Request $request
    ) {}

    public function handle(Login $event): void
    {
        $timezone = $this->request->input('timezone');

        if (! $timezone || ! is_string($timezone)) {
            return;
        }

        // Validate it's a real timezone identifier
        if (! in_array($timezone, timezone_identifiers_list(\DateTimeZone::ALL), true)) {
            return;
        }

        $user = $event->user;

        // Only update if the timezone has changed
        if ($user->timezone !== $timezone) {
            $user->timezone = $timezone;
            $user->save();
        }
    }
}
