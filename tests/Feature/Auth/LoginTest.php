<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get("/login");

        $response->assertStatus(200);
    }
}
