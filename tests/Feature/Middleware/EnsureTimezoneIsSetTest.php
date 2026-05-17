<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Tests\TestCase;

class EnsureTimezoneIsSetTest extends TestCase
{
    public function test_authenticated_user_timezone_is_applied(): void
    {
        $user = User::factory()->create([
            'timezone' => 'America/New_York',
        ]);

        $this->actingAs($user)->get('/');

        $this->assertSame('America/New_York', config('app.timezone'));
    }
}
