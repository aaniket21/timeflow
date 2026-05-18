<?php

namespace Tests\Feature\Pages;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseSevenPagesTest extends TestCase
{
    public function test_phase_seven_routes_render_inertia_pages(): void
    {
        $routes = [
            '/analytics' => 'Analytics',
            '/projects' => 'Projects',
            '/timetable' => 'Timetable',
            '/goals' => 'Goals',
            '/habits' => 'Habits',
            '/plans' => 'Plans',
            '/reports' => 'Reports',
            '/settings' => 'Settings',
            '/achievements' => 'Achievements',
            '/leaderboard' => 'Leaderboard',
            '/login' => 'Auth/Login',
            '/register' => 'Auth/Register',
            '/forgot-password' => 'Auth/ForgotPassword',
            '/reset-password' => 'Auth/ResetPassword',
            '/onboarding' => 'Onboarding',
        ];

        foreach ($routes as $uri => $component) {
            $response = $this->get($uri);

            $response->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }
}
