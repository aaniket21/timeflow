<?php

namespace Tests\Feature\Gamification;

use App\Models\XpTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    public function test_leaderboard_ranks_users_by_weekly_xp(): void
    {
        // Flush leaderboard cache to prevent stale data from other tests
        Cache::forget('gamification:leaderboard');

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
            'reference_type' => 'date',
            'reference_id' => null,
            'created_at' => $weekStart->copy()->addDay(),
        ]);

        XpTransaction::create([
            'user_id' => $runnerUp->id,
            'amount' => 80,
            'reason' => 'test_weekly',
            'reference_type' => 'date',
            'reference_id' => null,
            'created_at' => $weekStart->copy()->addDay(),
        ]);

        Sanctum::actingAs($leader);

        $response = $this->getJson('/api/gamification/leaderboard');

        $response->assertOk();

        $data = $response->json('data');

        // Find our test users in the leaderboard (other tests may have created opt-in users)
        $leaderEntry = collect($data)->firstWhere('user_id', $leader->id);
        $runnerUpEntry = collect($data)->firstWhere('user_id', $runnerUp->id);

        $this->assertNotNull($leaderEntry, 'Leader should appear in leaderboard');
        $this->assertNotNull($runnerUpEntry, 'Runner-up should appear in leaderboard');
        $this->assertSame(120, $leaderEntry['xp']);
        $this->assertSame(80, $runnerUpEntry['xp']);

        // Leader should rank higher than runner-up
        $this->assertLessThan($runnerUpEntry['rank'], $leaderEntry['rank']);
    }
}
