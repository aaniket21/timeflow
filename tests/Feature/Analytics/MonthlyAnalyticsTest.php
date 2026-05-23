<?php

namespace Tests\Feature\Analytics;

use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonthlyAnalyticsTest extends TestCase
{
    public function test_monthly_analytics_returns_summary_metrics(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->for($user)->create();
        $projectB = Project::factory()->for($user)->create();

        $dayOneStart = Carbon::parse('2026-05-03 09:00:00');
        $dayOneEnd = Carbon::parse('2026-05-03 11:00:00');
        $dayTwoStartA = Carbon::parse('2026-05-10 10:00:00');
        $dayTwoEndA = Carbon::parse('2026-05-10 11:00:00');
        $dayTwoStartB = Carbon::parse('2026-05-10 13:00:00');
        $dayTwoEndB = Carbon::parse('2026-05-10 15:00:00');

        TimeSession::factory()->for($user)->create([
            'project_id' => $projectA->id,
            'started_at' => $dayOneStart,
            'ended_at' => $dayOneEnd,
            'duration_seconds' => $dayOneEnd->diffInSeconds($dayOneStart, true),
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $projectA->id,
            'started_at' => $dayTwoStartA,
            'ended_at' => $dayTwoEndA,
            'duration_seconds' => $dayTwoEndA->diffInSeconds($dayTwoStartA, true),
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $projectB->id,
            'started_at' => $dayTwoStartB,
            'ended_at' => $dayTwoEndB,
            'duration_seconds' => $dayTwoEndB->diffInSeconds($dayTwoStartB, true),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/analytics/monthly?month=2026-05');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.month', '2026-05')
            ->assertJsonPath('data.total_seconds', 18000)
            ->assertJsonPath('data.days_logged', 2)
            ->assertJsonPath('data.avg_daily_seconds', 9000)
            ->assertJsonPath('data.best_day.date', '2026-05-10')
            ->assertJsonPath('data.best_day.total_seconds', 10800)
            ->assertJsonPath('data.worst_day.date', '2026-05-03')
            ->assertJsonPath('data.worst_day.total_seconds', 7200)
            ->assertJsonPath('data.top_project.id', $projectA->id)
            ->assertJsonPath('data.top_project.name', $projectA->name)
            ->assertJsonPath('data.top_project.total_seconds', 10800);
    }

    public function test_monthly_analytics_returns_daily_totals_and_top_projects(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Frontend build',
            'color' => '#123456',
        ]);
        $projectB = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Study blocks',
            'color' => '#654321',
        ]);

        $dayOneStart = Carbon::parse('2026-05-03 09:00:00');
        $dayOneEnd = Carbon::parse('2026-05-03 12:00:00');
        $dayTwoStart = Carbon::parse('2026-05-10 10:00:00');
        $dayTwoEnd = Carbon::parse('2026-05-10 11:00:00');

        TimeSession::factory()->for($user)->create([
            'project_id' => $projectA->id,
            'started_at' => $dayOneStart,
            'ended_at' => $dayOneEnd,
            'duration_seconds' => $dayOneEnd->diffInSeconds($dayOneStart, true),
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $projectB->id,
            'started_at' => $dayTwoStart,
            'ended_at' => $dayTwoEnd,
            'duration_seconds' => $dayTwoEnd->diffInSeconds($dayTwoStart, true),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/analytics/monthly?month=2026-05');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(31, 'data.daily_totals')
            ->assertJsonFragment([
                'date' => '2026-05-03',
                'total_seconds' => 10800,
            ])
            ->assertJsonFragment([
                'name' => 'Frontend build',
                'color' => '#123456',
                'total_seconds' => 10800,
            ])
            ->assertJsonFragment([
                'date' => '2026-05-03',
                'total_seconds' => 10800,
            ]);
    }
}
