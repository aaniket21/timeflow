<?php

namespace Tests\Feature\Gamification;

use App\Models\XpTransaction;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    public function test_leaderboard_ranks_users_by_weekly_xp(): void
    {
        $weekStart = Carbon::now()->startOfWeek();

        $leader = User::factory()->create();
        $leader->forceFill([
            'leaderboard_opt_in' => true,
            'leaderboard_alias' => 'Alpha',
        ])->save();

        $runnerUp = User::factory()->create();
        $runnerUp->forceFill([
            'leaderboard_opt_in' => true,
            'leaderboard_alias' => 'Bravo',
        ])->save();

        XpTransaction::create([
            'user_id' => $leader->id,
            'amount' => 120,
            'reason' => 'test_weekly',
            'meta' => ['date' => $weekStart->toDateString()],
            'created_at' => $weekStart->copy()->addDay(),
        ]);

        XpTransaction::create([
            'user_id' => $runnerUp->id,
            'amount' => 80,
            'reason' => 'test_weekly',
            'meta' => ['date' => $weekStart->toDateString()],
            'created_at' => $weekStart->copy()->addDay(),
        ]);

        Sanctum::actingAs($leader);

        $response = $this->getJson('/api/gamification/leaderboard');

        $response->assertOk()
            ->assertJsonPath('data.0.user_id', $leader->id)
            ->assertJsonPath('data.0.xp', 120)
            ->assertJsonPath('data.1.user_id', $runnerUp->id)
            ->assertJsonPath('data.1.xp', 80);
    }
}
