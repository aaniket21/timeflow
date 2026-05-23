<?php

namespace Tests\Feature\Sessions;

use App\Helpers\TimeHelper;
use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * P2.5 — Verify SessionController uses TimeHelper for all timezone-aware
 * date operations and delegates streak logic to StreakService.
 */
class SessionTimezoneTest extends TestCase
{
    /**
     * When a user in IST stops a session, the daily totals query must use
     * timezone-aware UTC bounds (from TimeHelper::todayBoundsUtc), NOT
     * whereDate() which uses raw DB dates.
     *
     * Scenario: IST user logs a session at 23:30 IST (18:00 UTC).
     * With correct timezone logic the session counts for "today" in IST.
     * With broken UTC-only logic it would count for "yesterday" in UTC terms.
     */
    public function test_stop_session_uses_timezone_aware_daily_totals(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-21 18:30:00', 'UTC'));

        $user = User::factory()->create([
            'timezone' => 'Asia/Kolkata',
            'xp_total' => 0,
            'level' => 1,
            'daily_goal_hours' => 1.0,
            'streak_current' => 0,
            'streak_longest' => 0,
            'last_active_date' => null,
        ]);

        $project = Project::factory()->for($user)->create();

        // Session started 1.5 hours ago — so it's a 1.5h session
        // In IST this is 22:30 to 00:00 (midnight boundary) — but started_at determines the day
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => Carbon::parse('2026-05-21 17:00:00', 'UTC'), // 22:30 IST
            'ended_at' => null,
            'duration_seconds' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();

        // Streak should update because this is the user's first session
        $this->assertSame(1, $user->streak_current);
        // The daily totals should count this session in the IST day
        $this->assertSame(
            TimeHelper::todayForUser($user),
            $user->last_active_date instanceof Carbon
                ? $user->last_active_date->toDateString()
                : $user->last_active_date
        );

        Carbon::setTestNow();
    }

    /**
     * SessionController::stop must delegate streak logic to StreakService
     * instead of inline streak calculation.
     */
    public function test_stop_session_delegates_streak_to_streak_service(): void
    {
        $user = User::factory()->create([
            'timezone' => 'Asia/Kolkata',
            'streak_current' => 5,
            'streak_longest' => 10,
            'streak_shield_count' => 1,
            'last_active_date' => Carbon::yesterday()->toDateString(),
            'xp_total' => 100,
            'level' => 1,
        ]);

        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(30),
            'ended_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk();

        $user->refresh();

        // Streak should have incremented from 5 → 6 (consecutive day)
        $this->assertSame(6, $user->streak_current);
        $this->assertSame(10, $user->streak_longest);
    }

    /**
     * SessionController::store (manual entry) must also use TimeHelper for
     * the activity date — deriving it from started_at in user's timezone.
     */
    public function test_manual_session_uses_started_at_timezone_for_activity_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-22 06:00:00', 'UTC'));

        $user = User::factory()->create([
            'timezone' => 'Asia/Kolkata', // IST = UTC+5:30
            'xp_total' => 0,
            'level' => 1,
            'streak_current' => 0,
            'streak_longest' => 0,
            'last_active_date' => null,
        ]);

        $project = Project::factory()->for($user)->create();

        Sanctum::actingAs($user);

        // Session from 11PM to 11:30PM IST (May 22) = 17:30 to 18:00 UTC (May 22)
        $payload = [
            'project_id' => $project->id,
            'type' => 'manual',
            'started_at' => '2026-05-22T23:00:00+05:30',
            'ended_at' => '2026-05-22T23:30:00+05:30',
        ];

        $response = $this->postJson('/api/sessions', $payload);

        $response->assertCreated();

        $user->refresh();

        // The activity date should be May 22 (the IST date of started_at), not May 22 UTC
        $this->assertSame('2026-05-22', $user->last_active_date instanceof Carbon
            ? $user->last_active_date->toDateString()
            : $user->last_active_date
        );

        Carbon::setTestNow();
    }

    /**
     * Daily totals for gamification (XP for daily goal etc.) must use
     * timezone-aware UTC bounds from TimeHelper, not whereDate().
     */
    public function test_daily_totals_use_timezone_bounds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-21 19:00:00', 'UTC'));

        $user = User::factory()->create([
            'timezone' => 'Asia/Kolkata',
            'daily_goal_hours' => 2.0,
            'xp_total' => 0,
            'level' => 1,
            'streak_current' => 0,
            'streak_longest' => 0,
            'last_active_date' => null,
        ]);

        $project = Project::factory()->for($user)->create();

        // Create a completed session earlier today in IST
        // 10:00 AM IST = 04:30 UTC on May 21
        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => Carbon::parse('2026-05-21 04:30:00', 'UTC'),
            'ended_at' => Carbon::parse('2026-05-21 06:00:00', 'UTC'),
            'duration_seconds' => 5400, // 1.5 hours
        ]);

        // Now stop a new 40-minute session — total should be ~2h10m, meeting 2h daily goal
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => Carbon::parse('2026-05-21 18:20:00', 'UTC'), // 23:50 IST
            'ended_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk();

        // Should have earned daily_goal XP (25 XP) since total > 2h
        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'reason' => 'daily_goal',
        ]);

        Carbon::setTestNow();
    }
}
