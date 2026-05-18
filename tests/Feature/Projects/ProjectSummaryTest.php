<?php

namespace Tests\Feature\Projects;

use App\Models\Category;
use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectSummaryTest extends TestCase
{
    public function test_project_summary_returns_budget_and_progress(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Coding',
        ]);
        $project = Project::factory()->for($user)->create([
            'category_id' => $category->id,
            'name' => 'Frontend build',
            'color' => '#7C5CFC',
            'budget_hours' => 10,
        ]);

        $startOne = Carbon::parse('2026-05-18 09:00:00');
        $endOne = Carbon::parse('2026-05-18 10:00:00');
        $startTwo = Carbon::parse('2026-05-18 11:00:00');
        $endTwo = Carbon::parse('2026-05-18 11:30:00');

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $startOne,
            'ended_at' => $endOne,
            'duration_seconds' => $endOne->diffInSeconds($startOne, true),
        ]);

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => $startTwo,
            'ended_at' => $endTwo,
            'duration_seconds' => $endTwo->diffInSeconds($startTwo, true),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects/summary');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $project->id,
                'name' => 'Frontend build',
                'category' => 'Coding',
                'budget_hours' => 10.0,
                'total_seconds' => 5400,
                'progress_percent' => 15,
            ]);
    }
}
