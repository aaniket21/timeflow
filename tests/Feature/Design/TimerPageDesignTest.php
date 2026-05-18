<?php

namespace Tests\Feature\Design;

use Tests\TestCase;

class TimerPageDesignTest extends TestCase
{
    public function test_timer_page_ui_scaffold_present(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Timer.vue'));

        $this->assertNotFalse($vue);
        $this->assertStringContainsString('Active Timer', $vue);
        $this->assertStringContainsString('Session Log', $vue);
        $this->assertStringContainsString('Pomodoro', $vue);
        $this->assertStringContainsString('Start Session', $vue);
        $this->assertStringContainsString('Add past session', $vue);
    }
}
