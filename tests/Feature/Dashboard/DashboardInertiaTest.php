<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardInertiaTest extends TestCase
{
    public function test_root_route_returns_dashboard_inertia_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(function (Assert $page) {
            $page->component('Dashboard');
        });
    }

    public function test_dashboard_includes_navigation_sections(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(function (Assert $page) {
            $page->component('Dashboard')
                ->has('navigation.sections', 3)
                ->where('navigation.sections.0.label', 'Main')
                ->where('navigation.sections.1.label', 'Grow')
                ->where('navigation.sections.2.label', 'Export')
                ->where('navigation.sections.0.items.0.label', 'Dashboard');
        });
    }
}
