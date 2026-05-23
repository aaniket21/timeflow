<?php

namespace Tests\Feature\Goals;

use App\Models\Goal;
use App\Models\HabitLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GoalHabitTest extends TestCase
{
    public function test_user_can_create_goal(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'type' => 'daily_hours',
            'title' => 'Daily focus',
            'target_value' => 6,
        ];

        $response = $this->postJson('/api/goals', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.goal.type', 'daily_hours')
            ->assertJsonPath('data.goal.target_value', '6.00');

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'type' => 'daily_hours',
            'title' => 'Daily focus',
        ]);
    }

    public function test_user_cannot_exceed_habit_limit(): void
    {
        $user = User::factory()->create();

        Goal::factory()->count(6)->for($user)->create([
            'type' => 'habit',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'type' => 'habit',
            'title' => 'Seventh habit',
            'target_value' => 1,
        ];

        $response = $this->postJson('/api/goals', $payload);

        $response->assertStatus(422);
    }

    public function test_habit_log_awards_xp_and_records_log(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'xp_total' => 0,
            'level' => 1,
        ])->save();

        $goal = Goal::factory()->for($user)->create([
            'type' => 'habit',
            'title' => 'Exercise',
        ]);
        Goal::factory()->for($user)->create([
            'type' => 'habit',
            'title' => 'Reading',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/habits/{$goal->id}/log", [
            'date' => now()->toDateString(),
            'done' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.log.done', true)
            ->assertJsonPath('meta.xp_gained', 5);

        $this->assertDatabaseHas('habit_logs', [
            'user_id' => $user->id,
            'goal_id' => $goal->id,
            'date' => now()->toDateString(),
            'done' => true,
        ]);

        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'amount' => 5,
            'reason' => 'habit_complete',
        ]);
    }
}
