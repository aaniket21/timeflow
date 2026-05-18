<?php

namespace Tests\Feature\Design;

use Tests\TestCase;

class DesignTokensTest extends TestCase
{
    public function test_design_tokens_defined_in_app_css(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertNotFalse($css);
        $this->assertStringContainsString('--tf-bg-page: #F5F0E8', $css);
        $this->assertStringContainsString('--tf-bg-card: #FFFFFF', $css);
        $this->assertStringContainsString('--tf-text-primary: #1C1917', $css);
        $this->assertStringContainsString('--tf-violet: #7C5CFC', $css);
        $this->assertStringContainsString('.dark', $css);
        $this->assertStringContainsString('--tf-bg-page: #0C0C10', $css);
    }
}
