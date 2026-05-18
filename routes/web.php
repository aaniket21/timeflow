<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'navigation' => [
            'sections' => [
                [
                    'label' => 'Main',
                    'items' => [
                        ['label' => 'Dashboard', 'icon' => 'ti-layout-dashboard', 'active' => true],
                        ['label' => 'Timer', 'icon' => 'ti-player-play', 'active' => false],
                        ['label' => 'Analytics', 'icon' => 'ti-chart-bar', 'active' => false],
                        ['label' => 'Projects', 'icon' => 'ti-folder', 'active' => false, 'count' => 4],
                    ],
                ],
                [
                    'label' => 'Grow',
                    'items' => [
                        ['label' => 'Achievements', 'icon' => 'ti-trophy', 'active' => false],
                        ['label' => 'Goals', 'icon' => 'ti-target', 'active' => false],
                        ['label' => 'Leaderboard', 'icon' => 'ti-podium', 'active' => false],
                    ],
                ],
                [
                    'label' => 'Export',
                    'items' => [
                        ['label' => 'Reports', 'icon' => 'ti-file-analytics', 'active' => false],
                    ],
                ],
            ],
        ],
    ]);
});

Route::get('/focus', function () {
    return Inertia::render('FocusMode');
});

Route::get('/gamification/profile', function () {
    return Inertia::render('Gamification/Profile', [
        'profile' => [
            'xp_total' => 1240,
            'level' => 4,
            'level_title' => 'Dedicated',
            'next_level_xp' => 1600,
            'streak_current' => 14,
            'streak_longest' => 21,
            'badge_count' => 12,
            'last_active_date' => now()->toDateString(),
        ],
        'celebration' => [
            'title' => 'Level up!',
            'detail' => 'You are 360 XP away from Focused 5.',
            'action' => 'Keep the streak alive',
        ],
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
