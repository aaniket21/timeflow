<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HabitLogsTableTest extends TestCase
{
    public function test_habit_logs_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('habit_logs'));
        $this->assertTrue(Schema::hasColumns('habit_logs', [
            'id',
            'user_id',
            'goal_id',
            'date',
            'done',
        ]));
    }
}
