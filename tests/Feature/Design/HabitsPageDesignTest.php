<?php

namespace Tests\Feature\Design;

use Tests\TestCase;

class HabitsPageDesignTest extends TestCase
{
    public function test_habits_page_has_large_tap_targets(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Habits.vue'));

        $this->assertNotFalse($vue);
        $this->assertStringContainsString('height: 48px;', $vue);
    }
}
