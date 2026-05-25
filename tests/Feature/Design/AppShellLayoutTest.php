<?php

namespace Tests\Feature\Design;

use Tests\TestCase;

class AppShellLayoutTest extends TestCase
{
    public function test_app_shell_component_exists(): void
    {
        $vue = file_get_contents(resource_path('js/Layouts/AppShell.vue'));

        $this->assertNotFalse($vue);
        $this->assertStringContainsString('tf-shell', $vue);
        $this->assertStringContainsString('tf-sidebar', $vue);
    }

    public function test_shared_layout_classes_defined(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertNotFalse($css);
        $this->assertStringContainsString('.tf-shell', $css);
        $this->assertStringContainsString('.tf-topbar', $css);
        $this->assertStringContainsString('.tf-card', $css);
    }
}
