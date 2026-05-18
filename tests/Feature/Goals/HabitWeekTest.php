<?php

namespace Tests\Feature\Goals;

use App\Models\Goal;
use App\Models\HabitLog;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HabitWeekTest extends TestCase
{
    public function test_habit_week_returns_checks_and_streaks(): void
    {
        $user = User::factory()->create();
        $start = Carbon::parse('2026-05-11');
        $date = $start->copy()->addDays(2);

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
            'date' => $start->toDateString(),
            'done' => true,
        ]);
        HabitLog::factory()->create([
            'user_id' => $user->id,
            'goal_id' => $habit->id,
            'date' => $start->copy()->addDay()->toDateString(),
            'done' => true,
        ]);
        HabitLog::factory()->create([
            'user_id' => $user->id,
            'goal_id' => $habit->id,
            'date' => $date->toDateString(),
            'done' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/habits/week?start=2026-05-11&date=2026-05-13');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.start_date', '2026-05-11')
            ->assertJsonPath('data.end_date', '2026-05-17')
            ->assertJsonCount(2, 'data.habits')
            ->assertJsonPath('data.habits.0.id', $habit->id)
            ->assertJsonPath('data.habits.0.streak_current', 3)
            ->assertJsonPath('data.habits.0.checks.0', 1)
            ->assertJsonPath('data.habits.0.checks.1', 1)
            ->assertJsonPath('data.habits.0.checks.2', 1)
            ->assertJsonPath('data.habits.1.id', $other->id)
            ->assertJsonPath('data.habits.1.checks.0', 0);
    }
}
