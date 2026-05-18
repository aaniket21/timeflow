<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'timezone' => $user->timezone,
                    'notifications_enabled' => (bool) $user->notifications_enabled,
                    'email_digest_enabled' => (bool) $user->email_digest_enabled,
                    'pomodoro_work_min' => (int) ($user->pomodoro_work_min ?? 25),
                    'pomodoro_break_min' => (int) ($user->pomodoro_break_min ?? 5),
                    'daily_goal_hours' => (float) ($user->daily_goal_hours ?? 6),
                    'leaderboard_opt_in' => (bool) $user->leaderboard_opt_in,
                    'leaderboard_alias' => $user->leaderboard_alias,
                    'xp_total' => (int) ($user->xp_total ?? 0),
                    'streak_current' => (int) ($user->streak_current ?? 0),
                    'level' => (int) ($user->level ?? 1),
                ] : null,
            ],
        ]);
    }
}
