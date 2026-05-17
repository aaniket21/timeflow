<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoalsTableTest extends TestCase
{
    public function test_goals_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('goals'));
        $this->assertTrue(Schema::hasColumns('goals', [
            'id',
            'user_id',
            'type',
            'title',
            'target_value',
            'active',
            'created_at',
        ]));
    }
}
