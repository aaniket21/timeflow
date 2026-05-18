<?php

namespace Tests\Feature\Console;

use App\Jobs\SendWeeklyDigestJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class WeeklyDigestCommandTest extends TestCase
{
    public function test_weekly_digest_command_dispatches_job(): void
    {
        Bus::fake();

        Artisan::call('timeflow:send-weekly-digest');

        Bus::assertDispatched(SendWeeklyDigestJob::class);
    }
}
