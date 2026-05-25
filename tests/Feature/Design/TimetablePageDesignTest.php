<?php

namespace Tests\Feature\Design;

use Tests\TestCase;

class TimetablePageDesignTest extends TestCase
{
    public function test_timetable_page_is_scrollable_on_mobile(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Timetable.vue'));

        $this->assertNotFalse($vue);
        $this->assertStringContainsString('overflow-x: auto;', $vue);
        $this->assertStringContainsString('min-width: 600px;', $vue);
    }
}
