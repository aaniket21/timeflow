<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\GenerateReportRequest;
use App\Http\Requests\StartSessionRequest;
use Tests\TestCase;

class RequestRulesTest extends TestCase
{
    public function test_start_session_request_rules(): void
    {
        $rules = (new StartSessionRequest())->rules();

        $this->assertArrayHasKey('project_id', $rules);
        $this->assertContains('required', $this->normalizeRules($rules['project_id']));
    }

    public function test_create_project_request_rules(): void
    {
        $rules = (new CreateProjectRequest())->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $this->normalizeRules($rules['name']));
        $this->assertArrayHasKey('color', $rules);
        $this->assertContains('required', $this->normalizeRules($rules['color']));
    }

    public function test_generate_report_request_rules(): void
    {
        $rules = (new GenerateReportRequest())->rules();

        $this->assertArrayHasKey('title', $rules);
        $this->assertContains('required', $this->normalizeRules($rules['title']));
        $this->assertArrayHasKey('date_from', $rules);
        $this->assertContains('required', $this->normalizeRules($rules['date_from']));
        $this->assertArrayHasKey('date_to', $rules);
        $this->assertContains('required', $this->normalizeRules($rules['date_to']));
    }

    private function normalizeRules(array|string $rules): array
    {
        if (is_string($rules)) {
            return explode('|', $rules);
        }

        return $rules;
    }
}
