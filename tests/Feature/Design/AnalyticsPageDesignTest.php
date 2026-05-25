<?php

namespace Tests\Feature\Design;

use Tests\TestCase;

class AnalyticsPageDesignTest extends TestCase
{
    public function test_analytics_page_has_scrollable_tabs(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Analytics.vue'));

        $this->assertNotFalse($vue);
        $this->assertStringContainsString('overflow-x: auto;', $vue);
    }
}
