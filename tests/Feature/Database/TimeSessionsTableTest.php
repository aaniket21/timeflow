<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TimeSessionsTableTest extends TestCase
{
    public function test_time_sessions_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('time_sessions'));
        $this->assertTrue(Schema::hasColumns('time_sessions', [
            'id',
            'user_id',
            'project_id',
            'started_at',
            'ended_at',
            'duration_seconds',
            'notes',
            'label',
            'label_type',
            'is_pomodoro',
            'created_at',
        ]));
    }
}
