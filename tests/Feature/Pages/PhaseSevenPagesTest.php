<?php

namespace Tests\Feature\Pages;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseSevenPagesTest extends TestCase
{
    public function test_phase_seven_routes_render_inertia_pages(): void
    {
        $user = User::factory()->create();

        // Auth-protected routes — require authentication
        $authRoutes = [
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
            '/onboarding' => 'Onboarding',
        ];

        foreach ($authRoutes as $uri => $component) {
            $response = $this->actingAs($user)->get($uri);

            $response->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }

    public function test_guest_routes_render_inertia_pages(): void
    {
        // Guest routes — accessible without auth (use a fresh app instance)
        $guestRoutes = [
            '/login' => 'Auth/Login',
            '/register' => 'Auth/Register',
            '/forgot-password' => 'Auth/ForgotPassword',
            '/reset-password' => 'Auth/ResetPassword',
        ];

        foreach ($guestRoutes as $uri => $component) {
            // Create a fresh app for each guest route to avoid session bleed from actingAs
            $response = $this->get($uri);

            $response->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }
}
