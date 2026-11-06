<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    /** Administrador puede listar usuarios registrados con filtros */
    public function test_admin_can_list_users(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/users?role=dropshipper');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'role',
                            'phone',
                            'document_id',
                            'is_active',
                        ],
                    ],
                ],
            ]);
    }

    /** Administrador puede crear un nuevo proveedor o dropshipper */
    public function test_admin_can_create_new_provider(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        Sanctum::actingAs($admin);

        $payload = [
            'name'                  => 'Importadora Cochabamba Mayorista',
            'email'                 => 'mayorista.cbba@dropana.bo',
            'password'              => 'password123',
            'role'                  => 'provider',
            'phone'                 => '+591 71239999',
            'document_id'           => '8910293 CB',
            'company_or_store_name' => 'MegaAlmacén Cochabamba',
            'city'                  => 'Cochabamba',
            'department'            => 'Cochabamba',
        ];

        $response = $this->postJson('/api/v1/admin/users', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.email', 'mayorista.cbba@dropana.bo')
            ->assertJsonPath('data.role', 'provider');

        $this->assertDatabaseHas('users', [
            'email' => 'mayorista.cbba@dropana.bo',
            'role'  => 'provider',
        ]);
    }

    /** Administrador puede suspender o reactivar una cuenta */
    public function test_admin_can_toggle_user_status(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $dropshipper = User::where('role', 'dropshipper')->first();

        Sanctum::actingAs($admin);

        // Desactivar
        $resDeactivate = $this->patchJson("/api/v1/admin/users/{$dropshipper->id}/toggle-status");
        $resDeactivate->assertStatus(200);
        $this->assertFalse($dropshipper->fresh()->is_active);

        // Reactivar
        $resActivate = $this->patchJson("/api/v1/admin/users/{$dropshipper->id}/toggle-status");
        $resActivate->assertStatus(200);
        $this->assertTrue($dropshipper->fresh()->is_active);
    }

    /** No-administradores no tienen acceso al módulo de usuarios (403) */
    public function test_non_admin_cannot_manage_users(): void
    {
        $dropshipper = User::where('role', 'dropshipper')->first();
        Sanctum::actingAs($dropshipper);

        $response = $this->getJson('/api/v1/admin/users');
        $response->assertStatus(403);

        $postResponse = $this->postJson('/api/v1/admin/users', [
            'name' => 'Intento Hack',
        ]);
        $postResponse->assertStatus(403);
    }
}
