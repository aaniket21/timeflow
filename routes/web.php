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
                        ['label' => 'Projects', 'icon' => 'ti-folder', 'active' => $isActive('Projects'), 'count' => 4],
                    ],
                ],
                [
                    'label' => 'Grow',
                    'items' => [
                        ['label' => 'Achievements', 'icon' => 'ti-trophy', 'active' => $isActive('Achievements')],
                        ['label' => 'Goals', 'icon' => 'ti-target', 'active' => $isActive('Goals')],
                        ['label' => 'Leaderboard', 'icon' => 'ti-podium', 'active' => $isActive('Leaderboard')],
                    ],
                ],
                [
                    'label' => 'Export',
                    'items' => [
                        ['label' => 'Reports', 'icon' => 'ti-file-analytics', 'active' => $isActive('Reports')],
                    ],
                ],
            ],
        ];
    }
}

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'navigation' => buildNavigation('Dashboard'),
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
        'navigation' => buildNavigation('Projects'),
    ]);
});

Route::get('/goals', function () {
    return Inertia::render('Goals', [
        'navigation' => buildNavigation('Goals'),
    ]);
});

Route::get('/habits', function () {
    return Inertia::render('Habits', [
        'navigation' => buildNavigation('Goals'),
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

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
});

Route::get('/register', function () {
    return Inertia::render('Auth/Register');
});

Route::get('/forgot-password', function () {
    return Inertia::render('Auth/ForgotPassword');
});

Route::get('/reset-password', function () {
    return Inertia::render('Auth/ResetPassword');
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
        'badges' => [
            ['id' => 1, 'name' => 'First Flame', 'icon' => 'FL', 'description' => '3-day streak', 'earned' => true, 'category' => 'consistency'],
            ['id' => 2, 'name' => 'Week Warrior', 'icon' => 'WW', 'description' => '7-day streak', 'earned' => true, 'category' => 'consistency'],
            ['id' => 3, 'name' => 'Mountain Climber', 'icon' => 'MC', 'description' => '30-day streak', 'earned' => false, 'category' => 'consistency'],
            ['id' => 4, 'name' => 'Tomato Head', 'icon' => 'TH', 'description' => 'First Pomodoro', 'earned' => true, 'category' => 'focus'],
            ['id' => 5, 'name' => 'Deep Diver', 'icon' => 'DD', 'description' => '4-hour focus session', 'earned' => false, 'category' => 'focus'],
            ['id' => 6, 'name' => 'First Hour', 'icon' => 'FH', 'description' => 'Log your first hour', 'earned' => true, 'category' => 'volume'],
            ['id' => 7, 'name' => '10 Hour Club', 'icon' => '10', 'description' => 'Log 10h in a week', 'earned' => false, 'category' => 'volume'],
            ['id' => 8, 'name' => 'Time Lord', 'icon' => 'TL', 'description' => 'Log 1000h', 'earned' => false, 'category' => 'volume'],
        ],
    ]);
});

Route::get('/gamification/leaderboard', function () {
    return Inertia::render('Gamification/Leaderboard', [
        'leaders' => [
            ['rank' => 1, 'name' => 'Alpha', 'xp' => 420],
            ['rank' => 2, 'name' => 'Bravo', 'xp' => 360],
            ['rank' => 3, 'name' => 'Nova', 'xp' => 330],
            ['rank' => 4, 'name' => 'Pulse', 'xp' => 280],
            ['rank' => 5, 'name' => 'Quill', 'xp' => 240],
        ],
    ]);
});

Route::get('/reports/share/{token}', [ReportController::class, 'share']);
