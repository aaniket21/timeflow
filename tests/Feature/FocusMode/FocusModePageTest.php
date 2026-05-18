<?php

namespace Tests\Feature\FocusMode;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FocusModePageTest extends TestCase
{
    public function test_focus_mode_page_renders(): void
    {
        $response = $this->get('/focus');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('FocusMode'));
    }
}
