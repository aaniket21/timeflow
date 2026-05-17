<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BadgesTableTest extends TestCase
{
    public function test_badges_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('badges'));
        $this->assertTrue(Schema::hasColumns('badges', [
            'id',
            'slug',
            'name',
            'description',
            'icon',
            'category',
            'xp_reward',
        ]));
    }
}
