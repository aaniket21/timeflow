<?php

namespace Tests\Feature\Plans;

use App\Models\DailyPlan;
use App\Models\Exam;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamPlanTest extends TestCase
{
    public function test_user_can_create_exam(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'subject' => 'Physics',
            'exam_date' => now()->addDays(10)->toDateString(),
            'notes' => 'Chapters 4-7',
        ];

        $response = $this->postJson('/api/exams', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.exam.subject', 'Physics');

        $this->assertDatabaseHas('exams', [
            'user_id' => $user->id,
            'subject' => 'Physics',
        ]);
    }

    public function test_exam_list_returns_upcoming_only(): void
    {
        $user = User::factory()->create();

        Exam::factory()->for($user)->create([
            'subject' => 'Past',
            'exam_date' => Carbon::now()->subDays(2)->toDateString(),
        ]);
        Exam::factory()->for($user)->create([
            'subject' => 'Upcoming',
            'exam_date' => Carbon::now()->addDays(5)->toDateString(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/exams');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Upcoming');
    }

    public function test_user_can_save_daily_plan(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'xp_total' => 0,
            'level' => 1,
        ])->save();

        Sanctum::actingAs($user);

        $payload = [
            'date' => now()->toDateString(),
            'tasks' => [
                ['text' => 'Task 1', 'done' => true],
                ['text' => 'Task 2', 'done' => true],
                ['text' => 'Task 3', 'done' => true],
            ],
        ];

        $response = $this->postJson('/api/daily-plans', $payload);

        $response->assertOk()
            ->assertJsonPath('meta.xp_gained', 30);

        $plan = DailyPlan::query()
            ->where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->first();

        $this->assertNotNull($plan);
        $this->assertCount(3, $plan->tasks);

        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'amount' => 30,
            'reason' => 'daily_plan_complete',
        ]);
    }
}
