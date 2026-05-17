<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyPlansTableTest extends TestCase
{
    public function test_daily_plans_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('daily_plans'));
        $this->assertTrue(Schema::hasColumns('daily_plans', [
            'id',
            'user_id',
            'date',
            'tasks',
            'created_at',
        ]));
    }
}
