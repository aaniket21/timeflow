<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ReportController;

if (! function_exists('buildNavigation')) {
    function buildNavigation(string $activeLabel): array
    {
        $isActive = fn (string $label): bool => $label === $activeLabel;

        return [
            'sections' => [
                [
                    'label' => 'Main',
                    'items' => [
                        ['label' => 'Dashboard', 'icon' => 'ti-layout-dashboard', 'active' => $isActive('Dashboard')],
                        ['label' => 'Timer', 'icon' => 'ti-player-play', 'active' => $isActive('Timer')],
                        ['label' => 'Analytics', 'icon' => 'ti-chart-bar', 'active' => $isActive('Analytics')],
                        ['label' => 'Projects', 'icon' => 'ti-folder', 'active' => $isActive('Projects')],
                        ['label' => 'Timetable', 'icon' => 'ti-table', 'active' => $isActive('Timetable')],
                    ],
                ],
                [
                    'label' => 'Grow',
                    'items' => [
                        ['label' => 'Achievements', 'icon' => 'ti-trophy', 'active' => $isActive('Achievements')],
                        ['label' => 'Goals', 'icon' => 'ti-target', 'active' => $isActive('Goals')],
                        ['label' => 'Habits', 'icon' => 'ti-checkbox', 'active' => $isActive('Habits')],
                        ['label' => 'Leaderboard', 'icon' => 'ti-podium', 'active' => $isActive('Leaderboard')],
                    ],
                ],
                [
                    'label' => 'Export',
                    'items' => [
                        ['label' => 'Reports', 'icon' => 'ti-file-analytics', 'active' => $isActive('Reports')],
                        ['label' => 'Settings', 'icon' => 'ti-settings', 'active' => $isActive('Settings')],
                    ],
                ],
            ],
        ];
    }
}

Route::get('/health', \App\Http\Controllers\HealthController::class);

// --- Guest routes (login, register, etc.) ---

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return Inertia::render('Auth/Login');
    })->name('login');

    Route::get('/register', function () {
        return Inertia::render('Auth/Register');
    })->name('register');

    Route::get('/forgot-password', function () {
        return Inertia::render('Auth/ForgotPassword');
    })->name('password.request');

    Route::get('/reset-password', function () {
        return Inertia::render('Auth/ResetPassword');
    })->name('password.reset');
});

// --- Logout route ---

Route::post('/logout', function () {
    auth()->guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');

// --- Authenticated routes ---

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (Illuminate\Http\Request $request) {
        return Inertia::render('Dashboard', [
            'navigation' => buildNavigation('Dashboard'),
            'gamification' => Inertia::defer(fn() => app(\App\Http\Controllers\GamificationController::class)->profile($request)->getData(true)['data'] ?? null),
            'analytics' => Inertia::defer(fn() => app(\App\Http\Controllers\AnalyticsController::class)->daily($request)->getData(true)['data'] ?? null),
            'recent' => Inertia::defer(fn() => app(\App\Http\Controllers\SessionController::class)->recent($request)->getData(true)['data'] ?? null),
            'challenges' => Inertia::defer(fn() => app(\App\Http\Controllers\ChallengeController::class)->today($request)->getData(true)['data'] ?? null),
            'habits' => Inertia::defer(fn() => app(\App\Http\Controllers\GoalController::class)->todayHabits($request)->getData(true)['data'] ?? null),
            'timetable' => Inertia::defer(fn() => app(\App\Http\Controllers\TimetableController::class)->today($request)->getData(true)['data'] ?? null),
        ]);
    });

    Route::get('/timer', function () {
        return Inertia::render('Timer', [
            'navigation' => buildNavigation('Timer'),
        ]);
    });

    Route::get('/analytics', function () {
        return Inertia::render('Analytics', [
            'navigation' => buildNavigation('Analytics'),
        ]);
    });

    Route::get('/projects', function () {
        return Inertia::render('Projects', [
            'navigation' => buildNavigation('Projects'),
        ]);
    });

    Route::get('/timetable', function () {
        return Inertia::render('Timetable', [
            'navigation' => buildNavigation('Timetable'),
        ]);
    });

    Route::get('/goals', function () {
        return Inertia::render('Goals', [
            'navigation' => buildNavigation('Goals'),
        ]);
    });

    Route::get('/habits', function () {
        return Inertia::render('Habits', [
            'navigation' => buildNavigation('Habits'),
        ]);
    });

    Route::get('/plans', function () {
        return Inertia::render('Plans', [
            'navigation' => buildNavigation('Goals'),
        ]);
    });

    Route::get('/reports', function () {
        return Inertia::render('Reports', [
            'navigation' => buildNavigation('Reports'),
        ]);
    });

    Route::get('/settings', function () {
        return Inertia::render('Settings', [
            'navigation' => buildNavigation('Settings'),
        ]);
    });

    Route::get('/achievements', function () {
        return Inertia::render('Achievements', [
            'navigation' => buildNavigation('Achievements'),
        ]);
    });

    Route::get('/leaderboard', function () {
        return Inertia::render('Leaderboard', [
            'navigation' => buildNavigation('Leaderboard'),
        ]);
    });

    Route::get('/focus', function () {
        return Inertia::render('FocusMode');
    });

    Route::get('/onboarding', function () {
        return Inertia::render('Onboarding');
    });

    Route::get('/gamification/profile', function () {
        return Inertia::render('Gamification/Profile', [
            'navigation' => buildNavigation('Achievements'),
        ]);
    });

    Route::get('/gamification/badges', function () {
        return Inertia::render('Gamification/Badges', [
            'navigation' => buildNavigation('Achievements'),
        ]);
    });

    Route::get('/gamification/leaderboard', function () {
        return Inertia::render('Gamification/Leaderboard', [
            'navigation' => buildNavigation('Leaderboard'),
        ]);
    });
});

// --- Public routes ---
Route::get('/reports/share/{token}', [ReportController::class, 'share']);
