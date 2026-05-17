<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsersTableTest extends TestCase
{
    public function test_users_table_has_timeflow_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'avatar_url',
            'timezone',
            'level',
            'xp_total',
            'streak_current',
            'streak_longest',
            'streak_shield',
            'last_active_date',
            'daily_goal_hours',
            'pomodoro_work_min',
            'pomodoro_break_min',
            'notifications_enabled',
            'leaderboard_opt_in',
            'leaderboard_alias',
            'email_digest_enabled',
            'deleted_at',
        ]));
    }
}
