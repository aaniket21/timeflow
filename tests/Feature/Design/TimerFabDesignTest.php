<?php

namespace Tests\Feature\Design;

use Tests\TestCase;

class TimerFabDesignTest extends TestCase
{
    public function test_timer_page_has_fab_styles(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Timer.vue'));

        $this->assertNotFalse($vue);
        $this->assertStringContainsString('fab-container', $vue);
        $this->assertStringContainsString('fab-btn', $vue);
    }
}
