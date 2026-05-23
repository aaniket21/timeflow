<?php

namespace Tests\Feature\Challenges;

use App\Models\DailyChallenge;
use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DailyChallengeCompletionTest extends TestCase
{
    public function test_hours_logged_challenge_completes_and_awards_xp(): void
    {
        $challenge = DailyChallenge::firstOrCreate(
            ['slug' => 'log-1-hour'],
            [
                'title' => 'Log at least 1 hour',
                'description' => 'Log 1 hour of focused work today',
                'condition_type' => 'hours_logged',
                'condition_value' => 1,
                'xp_reward' => 50,
            ]
        );

        $user = User::factory()->create();
        $user->forceFill([
            'last_active_date' => now()->toDateString(),
            'streak_current' => 1,
            'streak_longest' => 1,
        ])->save();
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(70),
            'ended_at' => null,
            'duration_seconds' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk();

        $this->assertDatabaseHas('user_challenge_completions', [
            'user_id' => $user->id,
            'completed_on' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'reason' => 'daily_challenge',
        ]);
    }
}
