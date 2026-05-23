<?php

namespace Tests\Feature\Badges;

use App\Models\Badge;
use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StreakBadgeTest extends TestCase
{
    public function test_three_day_streak_awards_first_flame_badge(): void
    {
        $badge = Badge::firstOrCreate([
            'slug' => 'first_flame',
        ], [
            'name' => 'First Flame',
            'description' => '3-day streak',
            'icon' => '🔥',
            'condition_type' => 'streak',
            'condition_value' => 3,
        ]);

        $user = User::factory()->create();
        $user->forceFill([
            'streak_current' => 2,
            'last_active_date' => now()->subDay()->toDateString(),
        ])->save();
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(10),
            'ended_at' => null,
            'duration_seconds' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk()
            ->assertJsonPath('meta.badges_earned.0.slug', 'first_flame');

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
    }

    public function test_seven_day_streak_awards_week_warrior_badge(): void
    {
        $badge = Badge::firstOrCreate([
            'slug' => 'week_warrior',
        ], [
            'name' => 'Week Warrior',
            'description' => '7-day streak',
            'icon' => '💪',
            'condition_type' => 'streak',
            'condition_value' => 7,
        ]);

        $user = User::factory()->create();
        $user->forceFill([
            'streak_current' => 6,
            'last_active_date' => now()->subDay()->toDateString(),
        ])->save();
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(10),
            'ended_at' => null,
            'duration_seconds' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk()
            ->assertJsonFragment(['slug' => 'week_warrior']);

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
    }
}
