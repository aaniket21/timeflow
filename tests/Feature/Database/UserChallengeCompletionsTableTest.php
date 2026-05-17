<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserChallengeCompletionsTableTest extends TestCase
{
    public function test_user_challenge_completions_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('user_challenge_completions'));
        $this->assertTrue(Schema::hasColumns('user_challenge_completions', [
            'id',
            'user_id',
            'challenge_id',
            'date',
            'completed_at',
        ]));
    }
}
