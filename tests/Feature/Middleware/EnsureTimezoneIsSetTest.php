<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnsureTimezoneIsSetTest extends TestCase
{
    public function test_authenticated_user_timezone_is_applied(): void
    {
        // Register a test route that captures the timezone during request processing
        Route::middleware(['auth:sanctum', \App\Http\Middleware\SetUserTimezone::class])
            ->get('/test-timezone', function () {
                return response()->json([
                    'timezone' => date_default_timezone_get(),
                ]);
            });

        $user = User::factory()->create([
            'timezone' => 'America/New_York',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/test-timezone');

        $response->assertOk()
            ->assertJsonPath('timezone', 'America/New_York');
    }
}
