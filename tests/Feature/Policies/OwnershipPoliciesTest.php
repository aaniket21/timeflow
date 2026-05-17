<?php

namespace Tests\Feature\Policies;

use App\Models\Project;
use App\Models\Report;
use App\Models\TimeSession;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class OwnershipPoliciesTest extends TestCase
{
    public function test_owner_can_view_and_others_cannot_for_project(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $project));
        $this->assertFalse(Gate::forUser($other)->allows('view', $project));
    }

    public function test_owner_can_view_and_others_cannot_for_session(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $session = TimeSession::factory()->for($owner)->create();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $session));
        $this->assertFalse(Gate::forUser($other)->allows('view', $session));
    }

    public function test_owner_can_view_and_others_cannot_for_report(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $report = Report::factory()->for($owner)->create();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $report));
        $this->assertFalse(Gate::forUser($other)->allows('view', $report));
    }
}
