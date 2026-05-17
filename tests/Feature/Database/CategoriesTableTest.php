<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoriesTableTest extends TestCase
{
    public function test_categories_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasColumns('categories', [
            'id',
            'user_id',
            'name',
            'color',
            'icon',
            'parent_id',
            'archived',
            'created_at',
        ]));
    }
}
