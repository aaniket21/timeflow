<?php

namespace Tests\Feature\Timer;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TimerPageTest extends TestCase
{
    public function test_timer_page_renders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/timer');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Timer'));
    }

    public function test_timer_page_includes_navigation_sections(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/timer');

        $response->assertInertia(function (Assert $page) {
            $page->component('Timer')
                ->has('navigation.sections', 3)
                ->where('navigation.sections.0.label', 'Main')
                ->where('navigation.sections.1.label', 'Grow')
                ->where('navigation.sections.2.label', 'Export')
                ->where('navigation.sections.0.items.1.label', 'Timer');
        });
    }
}
