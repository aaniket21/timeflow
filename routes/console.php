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
