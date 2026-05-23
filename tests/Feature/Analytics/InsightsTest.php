<?php

namespace Tests\Feature\Analytics;

use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InsightsTest extends TestCase
{
    public function test_insights_include_short_session_warning(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(20),
            'ended_at' => now()->subMinutes(10),
            'duration_seconds' => 600,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/analytics/insights');

        $response->assertOk()
            ->assertJsonPath('data.0.type', 'short_sessions');
    }
}
