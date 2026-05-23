<?php

namespace Tests\Feature\Analytics;

use App\Models\Category;
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
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $startTwo,
            'ended_at' => $endTwo,
            'duration_seconds' => $endTwo->diffInSeconds($startTwo, true),
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

    public function test_daily_analytics_returns_pomodoro_count(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $date = now()->toDateString();

        $start = Carbon::parse("{$date} 07:00:00");
        $end = Carbon::parse("{$date} 07:25:00");

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $start,
            'ended_at' => $end,
            'duration_seconds' => $end->diffInSeconds($start, true),
            'is_pomodoro' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/analytics/daily?date={$date}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pomodoro_count', 1);
    }

    public function test_daily_analytics_returns_hourly_breakdown_and_sessions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Study',
            'color' => '#123456',
        ]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Frontend build',
            'color' => '#654321',
            'category_id' => $category->id,
        ]);

        $date = now()->toDateString();
        $startOne = Carbon::parse("{$date} 09:00:00");
        $endOne = Carbon::parse("{$date} 09:30:00");
        $startTwo = Carbon::parse("{$date} 11:00:00");
        $endTwo = Carbon::parse("{$date} 11:45:00");

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $startOne,
            'ended_at' => $endOne,
            'duration_seconds' => $endOne->diffInSeconds($startOne, true),
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $startTwo,
            'ended_at' => $endTwo,
            'duration_seconds' => $endTwo->diffInSeconds($startTwo, true),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/analytics/daily?date={$date}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(24, 'data.hourly_breakdown')
            ->assertJsonPath('data.longest_session_seconds', 2700)
            ->assertJsonFragment([
                'hour' => 9,
                'total_seconds' => 1800,
            ])
            ->assertJsonFragment([
                'label' => 'Frontend build',
                'category' => 'Study',
                'color' => '#654321',
            ]);
    }
}
