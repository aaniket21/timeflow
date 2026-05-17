<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserBadgesTableTest extends TestCase
{
    public function test_user_badges_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('user_badges'));
        $this->assertTrue(Schema::hasColumns('user_badges', [
            'id',
            'user_id',
            'badge_id',
            'earned_at',
        ]));
    }
}
