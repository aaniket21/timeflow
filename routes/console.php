<?php

use App\Jobs\SendWeeklyDigestJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('timeflow:send-weekly-digest', function () {
    SendWeeklyDigestJob::dispatch();
})->purpose('Send weekly digest emails')->weeklyOn(1, '07:00');
