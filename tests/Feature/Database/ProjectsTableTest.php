<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectsTableTest extends TestCase
{
    public function test_projects_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('projects'));
        $this->assertTrue(Schema::hasColumns('projects', [
            'id',
            'user_id',
            'category_id',
            'name',
            'color',
            'icon',
            'client_name',
            'budget_hours',
            'is_archived',
            'created_at',
        ]));
    }
}
