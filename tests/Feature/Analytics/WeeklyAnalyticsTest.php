<?php

namespace Tests\Feature\Analytics;

use App\Models\Category;
use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeeklyAnalyticsTest extends TestCase
{
    public function test_weekly_analytics_returns_summary_metrics(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $startDate = Carbon::parse('2026-05-11');
        $endDate = $startDate->copy()->addDays(6);

        $dayOneStart = $startDate->copy()->setTime(9, 0);
        $dayOneEnd = $startDate->copy()->setTime(11, 0);
        $dayTwoStart = $startDate->copy()->addDays(2)->setTime(10, 0);
        $dayTwoEnd = $startDate->copy()->addDays(2)->setTime(11, 0);
        $dayThreeStart = $startDate->copy()->addDays(4)->setTime(12, 0);
        $dayThreeEnd = $startDate->copy()->addDays(4)->setTime(15, 0);

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $dayOneStart,
            'ended_at' => $dayOneEnd,
            'duration_seconds' => $dayOneEnd->diffInSeconds($dayOneStart, true),
            'type' => 'timer',
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $dayTwoStart,
            'ended_at' => $dayTwoEnd,
            'duration_seconds' => $dayTwoEnd->diffInSeconds($dayTwoStart, true),
            'type' => 'timer',
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $dayThreeStart,
            'ended_at' => $dayThreeEnd,
            'duration_seconds' => $dayThreeEnd->diffInSeconds($dayThreeStart, true),
            'type' => 'timer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/analytics/weekly?start=2026-05-11');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.start_date', $startDate->toDateString())
            ->assertJsonPath('data.end_date', $endDate->toDateString())
            ->assertJsonPath('data.total_seconds', 21600)
            ->assertJsonPath('data.days_logged', 3)
            ->assertJsonPath('data.avg_daily_seconds', 7200)
            ->assertJsonPath('data.best_day.date', '2026-05-15')
            ->assertJsonPath('data.best_day.total_seconds', 10800)
            ->assertJsonPath('data.worst_day.date', '2026-05-13')
            ->assertJsonPath('data.worst_day.total_seconds', 3600);
    }

    public function test_weekly_analytics_returns_daily_totals_and_category_breakdown(): void
    {
        $user = User::factory()->create([
            'daily_goal_hours' => 5,
        ]);
        $categoryA = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Study',
            'color' => '#123456',
        ]);
        $categoryB = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Meetings',
            'color' => '#654321',
        ]);
        $projectA = Project::factory()->create([
            'user_id' => $user->id,
            'category_id' => $categoryA->id,
        ]);
        $projectB = Project::factory()->create([
            'user_id' => $user->id,
            'category_id' => $categoryB->id,
        ]);

        $startDate = Carbon::parse('2026-05-11');
        $dayOneStart = $startDate->copy()->setTime(9, 0);
        $dayOneEnd = $startDate->copy()->setTime(11, 0);
        $dayTwoStart = $startDate->copy()->addDays(2)->setTime(13, 0);
        $dayTwoEnd = $startDate->copy()->addDays(2)->setTime(14, 0);

        TimeSession::factory()->for($user)->create([
            'project_id' => $projectA->id,
            'started_at' => $dayOneStart,
            'ended_at' => $dayOneEnd,
            'duration_seconds' => $dayOneEnd->diffInSeconds($dayOneStart, true),
            'type' => 'timer',
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $projectB->id,
            'started_at' => $dayTwoStart,
            'ended_at' => $dayTwoEnd,
            'duration_seconds' => $dayTwoEnd->diffInSeconds($dayTwoStart, true),
            'type' => 'timer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/analytics/weekly?start=2026-05-11');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(7, 'data.daily_totals')
            ->assertJsonFragment([
                'date' => '2026-05-11',
                'total_seconds' => 7200,
                'goal_seconds' => 18000,
            ])
            ->assertJsonFragment([
                'name' => 'Study',
                'color' => '#123456',
                'total_seconds' => 7200,
            ]);
    }
}
