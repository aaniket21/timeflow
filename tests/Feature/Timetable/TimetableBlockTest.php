<?php

namespace Tests\Feature\Timetable;

use App\Models\TimetableBlock;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimetableBlockTest extends TestCase
{
    public function test_user_can_create_timetable_block(): void
    {
        $user = User::factory()->create();
        $day = Carbon::now()->dayOfWeekIso;

        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Study block',
            'type' => 'study',
            'color' => '#0EA5E9',
            'day_of_week' => $day,
            'start_time' => '09:00',
            'end_time' => '10:30',
        ];

        $response = $this->postJson('/api/timetable/blocks', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.block.title', 'Study block');

        $this->assertDatabaseHas('timetable_blocks', [
            'user_id' => $user->id,
            'title' => 'Study block',
        ]);
    }

    public function test_conflicting_block_is_rejected(): void
    {
        $user = User::factory()->create();
        $day = Carbon::now()->dayOfWeekIso;

        TimetableBlock::factory()->for($user)->create([
            'day_of_week' => $day,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Overlapping',
            'type' => 'study',
            'color' => '#F97316',
            'day_of_week' => $day,
            'start_time' => '10:30',
            'end_time' => '11:30',
        ];

        $response = $this->postJson('/api/timetable/blocks', $payload);

        $response->assertStatus(422);
    }

    public function test_today_blocks_returns_matching_day(): void
    {
        $user = User::factory()->create();
        $day = Carbon::now()->dayOfWeekIso;

        TimetableBlock::factory()->for($user)->create([
            'title' => 'Today block',
            'day_of_week' => $day,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        TimetableBlock::factory()->for($user)->create([
            'title' => 'Other day',
            'day_of_week' => $day % 7 + 1,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/timetable/today');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Today block');
    }
}
