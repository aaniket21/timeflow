<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TimetableBlocksTableTest extends TestCase
{
    public function test_timetable_blocks_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('timetable_blocks'));
        $this->assertTrue(Schema::hasColumns('timetable_blocks', [
            'id',
            'user_id',
            'title',
            'type',
            'color',
            'project_id',
            'days_of_week',
            'start_time',
            'end_time',
            'active',
            'semester_end',
            'created_at',
        ]));
    }
}
