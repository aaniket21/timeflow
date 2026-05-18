<?php

namespace Tests\Feature\Goals;

use App\Models\Goal;
use App\Models\TimeSession;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GoalSummaryTest extends TestCase
{
    public function test_goal_summary_returns_progress_for_daily_and_weekly(): void
    {
        $user = User::factory()->create();
        $date = Carbon::parse('2026-05-18');

        $dailyGoal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'daily_hours',
            'title' => 'Daily focus',
            'target_value' => 6,
        ]);

        $weeklyGoal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'weekly_hours',
            'title' => 'Weekly focus',
            'target_value' => 30,
        ]);

        $startOne = $date->copy()->setTime(9, 0);
        $endOne = $date->copy()->setTime(11, 0);
        $startTwo = $date->copy()->addDays(2)->setTime(10, 0);
        $endTwo = $date->copy()->addDays(2)->setTime(16, 0);

        TimeSession::factory()->for($user)->create([
            'started_at' => $startOne,
            'ended_at' => $endOne,
            'duration_seconds' => $endOne->diffInSeconds($startOne, true),
        ]);

        TimeSession::factory()->for($user)->create([
            'started_at' => $startTwo,
            'ended_at' => $endTwo,
            'duration_seconds' => $endTwo->diffInSeconds($startTwo, true),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/goals/summary?date=2026-05-18');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $dailyGoal->id,
                'type' => 'daily_hours',
                'target_value' => 6.0,
                'current_value' => 2.0,
                'progress_percent' => 33,
            ])
            ->assertJsonFragment([
                'id' => $weeklyGoal->id,
                'type' => 'weekly_hours',
                'target_value' => 30.0,
                'current_value' => 8.0,
                'progress_percent' => 27,
            ]);
    }
}
