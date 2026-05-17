<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
