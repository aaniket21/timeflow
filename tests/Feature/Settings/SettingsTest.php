<?php

namespace Tests\Feature\Settings;

use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_user_can_update_profile_settings(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Updated Name',
            'avatar_url' => 'https://example.com/avatar.png',
            'timezone' => 'Asia/Kolkata',
        ];

        $response = $this->putJson('/api/settings/profile', $payload);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.avatar_url', 'https://example.com/avatar.png');

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('Asia/Kolkata', $user->timezone);
    }

    public function test_user_can_update_notification_settings(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'notifications_enabled' => false,
            'email_digest_enabled' => false,
        ];

        $response = $this->putJson('/api/settings/notifications', $payload);

        $response->assertOk()
            ->assertJsonPath('data.notifications_enabled', false)
            ->assertJsonPath('data.email_digest_enabled', false);

        $user->refresh();

        $this->assertFalse((bool) $user->notifications_enabled);
        $this->assertFalse((bool) $user->email_digest_enabled);
    }

    public function test_user_can_update_pomodoro_settings(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'pomodoro_work_min' => 30,
            'pomodoro_break_min' => 10,
        ];

        $response = $this->putJson('/api/settings/pomodoro', $payload);

        $response->assertOk()
            ->assertJsonPath('data.pomodoro_work_min', 30)
            ->assertJsonPath('data.pomodoro_break_min', 10);

        $user->refresh();

        $this->assertSame(30, $user->pomodoro_work_min);
        $this->assertSame(10, $user->pomodoro_break_min);
    }

    public function test_user_can_export_data(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHours(1),
            'duration_seconds' => 3600,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/settings/export');

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonCount(1, 'data.projects');
    }

    public function test_user_can_delete_account(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/settings/account');

        $response->assertOk();

        $user->refresh();

        $this->assertNotNull($user->deleted_at);
    }
}
