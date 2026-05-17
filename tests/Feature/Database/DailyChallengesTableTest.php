<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyChallengesTableTest extends TestCase
{
    public function test_daily_challenges_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('daily_challenges'));
        $this->assertTrue(Schema::hasColumns('daily_challenges', [
            'id',
            'title',
            'description',
            'type',
            'target_value',
            'xp_reward',
        ]));
    }
}
