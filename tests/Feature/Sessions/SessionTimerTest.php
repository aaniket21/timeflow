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

    public function test_api_requests_use_user_timezone(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Kolkata']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/sessions/start', [
            'type' => 'timer',
        ]);

        $response->assertCreated();
        
        $session = TimeSession::where('user_id', $user->id)->first();
        
        // Ensure the timestamp was saved and serialized in Asia/Kolkata
        $this->assertEquals('Asia/Kolkata', config('app.timezone'));
        $this->assertStringContainsString('+05:30', $response->json('data.session.started_at'));
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

    public function test_stop_session_accepts_notes(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(12),
            'ended_at' => null,
            'duration_seconds' => null,
            'notes' => null,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'notes' => 'Closing note after stop',
        ];

        $response = $this->postJson("/api/sessions/{$session->id}/stop", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.notes', 'Closing note after stop');

        $session->refresh();

        $this->assertSame('Closing note after stop', $session->notes);
    }

    public function test_active_session_returns_running_session(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(8),
            'ended_at' => null,
            'duration_seconds' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/sessions/active');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.id', $session->id)
            ->assertJsonPath('data.session.project_id', $project->id)
            ->assertJsonPath('data.session.ended_at', null);
    }

    public function test_stop_session_updates_streak(): void
    {
        $user = User::factory()->create([
            'streak_current' => 2,
            'streak_longest' => 3,
            'last_active_date' => now()->subDay()->toDateString(),
        ]);
        $project = Project::factory()->for($user)->create();
        $session = TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(30),
            'ended_at' => null,
            'duration_seconds' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sessions/{$session->id}/stop");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();

        $this->assertSame(now()->toDateString(), $user->last_active_date);
        $this->assertSame(3, $user->streak_current);
        $this->assertSame(3, $user->streak_longest);
    }

    public function test_stop_pomodoro_awards_xp(): void
    {
        $user = User::factory()->create([
            'xp_total' => 0,
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
            ->assertJsonPath('meta.xp_gained', 20);

        $user->refresh();

        $this->assertSame(20, $user->xp_total);

        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'amount' => 10,
            'reason' => 'pomodoro_complete',
        ]);
    }

    public function test_manual_pomodoro_updates_streak_and_xp(): void
    {
        $user = User::factory()->create([
            'xp_total' => 5,
            'pomodoro_work_min' => 25,
            'streak_current' => 0,
            'streak_longest' => 0,
            'last_active_date' => now()->subDay()->toDateString(),
        ]);
        $project = Project::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $startedAt = now()->subMinutes(35);
        $endedAt = now()->subMinutes(5);
        $expectedDuration = (int) $endedAt->diffInSeconds($startedAt, true);

        $payload = [
            'project_id' => $project->id,
            'type' => 'pomodoro',
            'started_at' => $startedAt->toIso8601String(),
            'ended_at' => $endedAt->toIso8601String(),
        ];

        $response = $this->postJson('/api/sessions', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.session.type', 'pomodoro')
            ->assertJsonPath('data.session.is_pomodoro', true)
            ->assertJsonPath('data.session.duration_seconds', $expectedDuration)
            ->assertJsonPath('meta.xp_gained', 20);

        $user->refresh();

        $this->assertSame(25, $user->xp_total);
        $this->assertSame(1, $user->streak_current);
        $this->assertSame(1, $user->streak_longest);
        $this->assertSame(now()->toDateString(), $user->last_active_date);

        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'amount' => 10,
            'reason' => 'pomodoro_complete',
        ]);
    }

    public function test_stop_pomodoro_levels_up_user(): void
    {
        $user = User::factory()->create([
            'xp_total' => 195,
            'level' => 1,
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
            ->assertJsonPath('meta.xp_gained', 20)
            ->assertJsonPath('meta.new_level', 2);

        $user->refresh();

        $this->assertSame(215, $user->xp_total);
        $this->assertSame(2, $user->level);
    }

    public function test_index_returns_sessions_with_project_and_category(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(30),
            'ended_at' => now(),
            'duration_seconds' => 1800,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/sessions');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.project_id', $project->id);
    }
}
