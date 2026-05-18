<?php

namespace Tests\Feature\Badges;

use App\Models\Badge;
use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PomodoroBadgeTest extends TestCase
{
    public function test_first_pomodoro_awards_badge(): void
    {
        $badge = Badge::create([
            'slug' => 'tomato_head',
            'name' => 'Tomato Head',
            'description' => 'First Pomodoro',
            'icon' => '🍅',
            'category' => 'focus',
            'xp_reward' => 0,
        ]);

        $user = User::factory()->create([
            'pomodoro_work_min' => 25,
        ]);
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'type' => 'pomodoro',
            'is_pomodoro' => true,
            'started_at' => now()->subMinutes(30),
            'ended_at' => null,
            'duration_seconds' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk()
            ->assertJsonPath('meta.badges_earned.0.slug', 'tomato_head');

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
    }
}
