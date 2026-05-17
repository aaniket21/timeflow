<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class XpTransactionsTableTest extends TestCase
{
    public function test_xp_transactions_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('xp_transactions'));
        $this->assertTrue(Schema::hasColumns('xp_transactions', [
            'id',
            'user_id',
            'amount',
            'reason',
            'meta',
            'created_at',
        ]));
    }
}
