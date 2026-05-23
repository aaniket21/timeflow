<?php

namespace Tests\Feature\FocusMode;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FocusModePageTest extends TestCase
{
    public function test_focus_mode_page_renders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/focus');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('FocusMode'));
    }
}
