<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportsTableTest extends TestCase
{
    public function test_reports_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('report_tokens'));
        $this->assertTrue(Schema::hasColumns('report_tokens', [
            'id',
            'user_id',
            'title',
            'date_from',
            'date_to',
            'status',
            'token',
            'file_path',
            'expires_at',
            'created_at',
        ]));
    }
}
