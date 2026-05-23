<?php

namespace Tests\Feature\Goals;

use App\Helpers\TimeHelper;
use App\Models\Goal;
use App\Models\HabitLog;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * P2.6 — Verify GoalController uses TimeHelper for timezone-aware dates.
 */
class GoalTimezoneTest extends TestCase
{
    /**
     * The summary endpoint must use TimeHelper::todayForUser() and
     * timezone-aware UTC bounds instead of whereDate().
     */
    public function test_summary_uses_timezone_aware_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-21 20:00:00', 'UTC'));

        $user = User::factory()->create([
            'timezone' => 'Asia/Kolkata', // IST = UTC+5:30 → 01:30 AM May 22
            'daily_goal_hours' => 2.0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/goals/summary');

        $response->assertOk()
            ->assertJsonPath('success', true);

        // In IST it's May 22, so the summary date should reflect that
        $response->assertJsonPath('data.date', '2026-05-22');

        Carbon::setTestNow();
    }

    /**
     * todayHabits must use TimeHelper::todayForUser() when no date specified.
     */
    public function test_today_habits_uses_user_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-21 20:00:00', 'UTC'));

        $user = User::factory()->create([
            'timezone' => 'Asia/Kolkata',
        ]);

        Goal::create([
            'user_id' => $user->id,
            'type' => 'habit',
            'title' => 'Read 30 min',
            'target_value' => 1,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/habits/today');

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Date should be May 22 in IST, not May 21 UTC
        $response->assertJsonPath('data.date', '2026-05-22');

        Carbon::setTestNow();
    }

    /**
     * logHabit must use TimeHelper::todayForUser() when no date provided.
     */
    public function test_log_habit_defaults_to_user_timezone_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-21 20:00:00', 'UTC'));

        $user = User::factory()->create([
            'timezone' => 'Asia/Kolkata',
        ]);

        $habit = Goal::create([
            'user_id' => $user->id,
            'type' => 'habit',
            'title' => 'Meditate',
            'target_value' => 1,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/habits/{$habit->id}/log", [
            'done' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // The logged date should be May 22 (IST), not May 21 (UTC)
        $this->assertDatabaseHas('habit_logs', [
            'user_id' => $user->id,
            'goal_id' => $habit->id,
            'date' => '2026-05-22',
            'done' => true,
        ]);

        Carbon::setTestNow();
    }
}
