<?php

namespace Tests\Feature\Reports;

use App\Models\Project;
use App\Models\Report;
use App\Models\TimeSession;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    public function test_user_can_generate_csv_report_and_share(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        TimeSession::factory()->for($user)->create([
            'project_id' => $project->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHours(1),
            'duration_seconds' => 3600,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Weekly CSV',
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
            'project_ids' => [$project->id],
            'format' => 'csv',
        ];

        $response = $this->postJson('/api/reports', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.report.title', 'Weekly CSV')
            ->assertJsonPath('data.report.format', 'csv');

        $reportId = $response->json('data.report.id');
        $report = Report::find($reportId);

        $this->assertNotNull($report);
        $this->assertNotNull($report->share_token);
        $this->assertNotNull($report->file_path);

        Storage::disk('local')->assertExists($report->file_path);

        $share = $this->get("/reports/share/{$report->share_token}");
        $share->assertOk()
            ->assertJsonPath('data.title', 'Weekly CSV');

        $download = $this->get("/api/reports/{$report->id}/download");
        $download->assertOk();
    }
}
