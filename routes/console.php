<?php

use App\Jobs\SendWeeklyDigestJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('timeflow:send-weekly-digest', function () {
    SendWeeklyDigestJob::dispatch();
})->purpose('Send weekly digest emails')->weeklyOn(1, '07:00');

// PRD §6 — Nightly streak recalculation at 01:00 UTC
Schedule::command('timeflow:check-streaks')->dailyAt('01:00')->withoutOverlapping();

// P4.7 - Send push notifications for streak risk at 8 PM user's timezone
Schedule::command('timeflow:streak-risk')->hourly()->withoutOverlapping();

// Daily backups
Schedule::command('backup:clean')->daily()->at('01:00')->withoutOverlapping();
Schedule::command('backup:run')->daily()->at('01:30')->withoutOverlapping();
