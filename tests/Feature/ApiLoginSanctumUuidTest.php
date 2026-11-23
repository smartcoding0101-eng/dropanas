<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLoginSanctumUuidTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_api_login_returns_token_with_uuid_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'dropshipper@bolidrop.bo',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'token',
                'user' => ['id', 'email', 'name', 'role'],
            ],
        ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Test using token to fetch orders
        $orderRes = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/orders');
        $orderRes->assertStatus(200);
    }
}
