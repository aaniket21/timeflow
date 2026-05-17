<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportsTableTest extends TestCase
{
    public function test_reports_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('reports'));
        $this->assertTrue(Schema::hasColumns('reports', [
            'id',
            'user_id',
            'title',
            'date_from',
            'date_to',
            'project_ids',
            'file_path',
            'share_token',
            'created_at',
        ]));
    }
}
