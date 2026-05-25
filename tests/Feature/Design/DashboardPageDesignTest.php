<?php

namespace Tests\Feature\Design;

use Tests\TestCase;

class DashboardPageDesignTest extends TestCase
{
    public function test_dashboard_is_mobile_first_card_stack(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Dashboard.vue'));

        $this->assertNotFalse($vue);
        $this->assertStringContainsString('@media (min-width: 768px)', $vue);
        $this->assertStringNotContainsString('@media (max-width: 768px)', $vue);
    }
}
