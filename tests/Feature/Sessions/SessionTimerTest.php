<?php

namespace Tests\Feature\Sessions;

use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SessionTimerTest extends TestCase
{
    public function test_start_session_creates_timer_session(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $payload = [
            'project_id' => $project->id,
            'type' => 'timer',
        ];

        $response = $this->postJson('/api/sessions/start', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.project_id', $project->id)
            ->assertJsonPath('data.session.type', 'timer');

        $this->assertDatabaseHas('time_sessions', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'type' => 'timer',
            'ended_at' => null,
        ]);
    }

    public function test_stop_session_sets_end_and_duration(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(5),
            'ended_at' => null,
            'duration_seconds' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.id', $session->id);

        $session->refresh();

        $this->assertNotNull($session->ended_at);
        $this->assertNotNull($session->duration_seconds);
        $this->assertGreaterThan(0, $session->duration_seconds);
    }

    public function test_manual_session_creates_completed_session(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $payload = [
            'project_id' => $project->id,
            'type' => 'manual',
            'started_at' => now()->subHours(2)->toIso8601String(),
            'ended_at' => now()->subHours(1)->toIso8601String(),
            'notes' => 'Backfilled session',
        ];

        $response = $this->postJson('/api/sessions', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.type', 'manual');

        $session = TimeSession::query()->where('user_id', $user->id)->latest('id')->first();

        $this->assertNotNull($session);
        $this->assertNotNull($session->ended_at);
        $this->assertNotNull($session->duration_seconds);
    }

    public function test_update_session_updates_notes_and_duration(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'type' => 'manual',
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHours(1),
            'duration_seconds' => 3600,
            'notes' => null,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'notes' => 'Updated notes',
            'ended_at' => now()->subMinutes(10)->toIso8601String(),
        ];

        $response = $this->putJson("/api/sessions/{$session->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.notes', 'Updated notes');

        $session->refresh();

        $this->assertSame('Updated notes', $session->notes);
        $this->assertNotNull($session->duration_seconds);
        $this->assertGreaterThan(0, $session->duration_seconds);
    }
}
