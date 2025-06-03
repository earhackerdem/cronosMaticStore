<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HealthCheckApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_status_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/status');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'message' => 'API is running'
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'timestamp'
            ]);
    }

    public function test_auth_status_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth-status');

        $response->assertStatus(401); // Unauthorized
    }

    public function test_auth_status_endpoint_returns_ok_and_user_when_authenticated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth-status');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'message' => 'Authentication is working',
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonStructure([
                'status',
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'is_admin',
                    'created_at',
                    'updated_at'
                ],
                'timestamp'
            ]);
    }
}
