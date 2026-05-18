<?php

namespace Tests\Feature\Analytics;

use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DailyAnalyticsTest extends TestCase
{
    public function test_daily_analytics_returns_summary_metrics(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $date = now()->toDateString();

        $startOne = Carbon::parse("{$date} 09:00:00");
        $endOne = Carbon::parse("{$date} 10:00:00");
        $startTwo = Carbon::parse("{$date} 11:00:00");
        $endTwo = Carbon::parse("{$date} 11:30:00");

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $startOne,
            'ended_at' => $endOne,
            'duration_seconds' => $endOne->diffInSeconds($startOne, true),
            'type' => 'timer',
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $startTwo,
            'ended_at' => $endTwo,
            'duration_seconds' => $endTwo->diffInSeconds($startTwo, true),
            'type' => 'timer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/analytics/daily?date={$date}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.date', $date)
            ->assertJsonPath('data.total_seconds', 5400)
            ->assertJsonPath('data.focus_sessions', 2)
            ->assertJsonPath('data.avg_session_seconds', 2700);
    }
}
