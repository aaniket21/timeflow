<?php

namespace Tests\Feature\Badges;

use App\Models\Badge;
use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VolumeBadgeTest extends TestCase
{
    public function test_first_hour_awards_first_hour_badge(): void
    {
        $badge = Badge::firstOrCreate([
            'slug' => 'first_hour',
        ], [
            'name' => 'First Hour',
            'description' => 'Log first hour',
            'icon' => 'clock',
            'category' => 'volume',
            'xp_reward' => 0,
        ]);

        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(65),
            'ended_at' => null,
            'duration_seconds' => null,
            'type' => 'timer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk()
            ->assertJsonPath('meta.badges_earned.0.slug', 'first_hour');

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
    }
}
