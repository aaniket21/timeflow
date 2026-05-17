<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class SanctumUserRouteTest extends TestCase
{
    public function test_api_user_route_requires_authentication(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }
}
