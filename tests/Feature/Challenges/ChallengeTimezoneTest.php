<?php

namespace Tests\Feature\Challenges;

use App\Models\DailyChallenge;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * P2.7 — Verify ChallengeController uses TimeHelper for timezone-aware dates.
 */
class ChallengeTimezoneTest extends TestCase
{
    public function test_today_challenge_uses_user_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-21 20:00:00', 'UTC'));

        DailyChallenge::firstOrCreate(
            ['slug' => 'tz-test-challenge'],
            [
                'title' => 'TZ Test Challenge',
                'description' => 'Test challenge for timezone',
                'condition_type' => 'hours_logged',
                'condition_value' => 2,
                'xp_reward' => 15,
            ]
        );

        $user = User::factory()->create([
            'timezone' => 'Asia/Kolkata', // IST = UTC+5:30 → 01:30 AM May 22
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/challenges/today');

        $response->assertOk()
            ->assertJsonPath('success', true);

        // In IST it's May 22, so the date should reflect that
        $response->assertJsonPath('data.date', '2026-05-22');

        Carbon::setTestNow();
    }
}
