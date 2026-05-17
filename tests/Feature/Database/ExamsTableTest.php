<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExamsTableTest extends TestCase
{
    public function test_exams_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('exams'));
        $this->assertTrue(Schema::hasColumns('exams', [
            'id',
            'user_id',
            'subject',
            'exam_date',
            'notes',
            'created_at',
        ]));
    }
}
