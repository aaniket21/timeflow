<?php

namespace Tests\Feature\Dashboard;

use App\Models\Category;
use App\Models\Goal;
use App\Models\HabitLog;
use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardDataTest extends TestCase
{
    public function test_heatmap_endpoint_returns_recent_days(): void
    {
        $user = User::factory()->create();
        $today = Carbon::now()->startOfDay();
        $older = $today->copy()->subDays(7);

        TimeSession::factory()->for($user)->create([
            'started_at' => $today->copy()->addHours(9),
            'ended_at' => $today->copy()->addHours(9)->addMinutes(30),
            'duration_seconds' => 1800,
            'type' => 'timer',
        ]);

        TimeSession::factory()->for($user)->create([
            'started_at' => $older->copy()->addHours(8),
            'ended_at' => $older->copy()->addHours(12),
            'duration_seconds' => 14400,
            'type' => 'timer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/analytics/heatmap');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(14, 'data.days')
            ->assertJsonFragment([
                'date' => $today->toDateString(),
                'total_seconds' => 1800,
                'level' => 1,
            ])
            ->assertJsonFragment([
                'date' => $older->toDateString(),
                'total_seconds' => 14400,
                'level' => 4,
            ]);
    }

    public function test_recent_sessions_endpoint_returns_project_labels(): void
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

        $recent = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'category_id' => null,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHours(1),
            'duration_seconds' => 3600,
            'type' => 'timer',
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => null,
            'category_id' => $category->id,
            'started_at' => now()->subDays(1),
            'ended_at' => now()->subDays(1)->addMinutes(45),
            'duration_seconds' => 2700,
            'type' => 'timer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/sessions/recent');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $recent->id)
            ->assertJsonPath('data.0.label', 'Frontend build')
            ->assertJsonPath('data.0.category', 'Study')
            ->assertJsonPath('data.0.color', '#654321');
    }

    public function test_habits_today_endpoint_returns_done_and_streak(): void
    {
        $user = User::factory()->create();
        $date = now()->toDateString();

        $habit = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'habit',
            'title' => 'Exercise',
        ]);
        $other = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'habit',
            'title' => 'Read',
        ]);

        HabitLog::factory()->create([
            'user_id' => $user->id,
            'goal_id' => $habit->id,
            'date' => $date,
            'done' => true,
        ]);
        HabitLog::factory()->create([
            'user_id' => $user->id,
            'goal_id' => $habit->id,
            'date' => now()->subDay()->toDateString(),
            'done' => true,
        ]);
        HabitLog::factory()->create([
            'user_id' => $user->id,
            'goal_id' => $habit->id,
            'date' => now()->subDays(2)->toDateString(),
            'done' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/habits/today');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.date', $date)
            ->assertJsonCount(2, 'data.habits')
            ->assertJsonFragment([
                'id' => $habit->id,
                'title' => 'Exercise',
                'done' => true,
                'streak_current' => 3,
            ])
            ->assertJsonFragment([
                'id' => $other->id,
                'title' => 'Read',
                'done' => false,
            ]);
    }
}
