<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\CarrierCoverage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCarrierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $dropshipper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::first() ?? Tenant::create([
            'id'           => '01a1f5b7-11c2-7350-89ce-f96f436c8ceb',
            'code'         => 'DROPANA_BO_TEST',
            'company_name' => 'Dropana Bolivia COD Test S.R.L.',
            'legal_nit'    => '1029384756',
            'domain'       => 'dropana.test',
            'is_active'    => true,
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Logística Dropana',
            'email'     => 'admin.logistica@dropana.bo',
            'password'  => bcrypt('password123'),
            'role'      => 'tenant_admin',
            'is_active' => true,
        ]);

        $this->dropshipper = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Vendedor Dropshipper Bolivia',
            'email'     => 'vendedor.bolivia@dropana.bo',
            'password'  => bcrypt('password123'),
            'role'      => 'dropshipper',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_admin_carriers(): void
    {
        $response = $this->get('/admin/carriers');
        $response->assertRedirect('/login');
    }

    public function test_dropshipper_cannot_access_admin_carriers(): void
    {
        $response = $this->actingAs($this->dropshipper)->get('/admin/carriers');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_carriers_index(): void
    {
        $carrier = Carrier::create([
            'tenant_id'       => $this->tenant->id,
            'name'            => 'DEX Bolivia Express',
            'code'            => 'dex_bolivia',
            'contact_name'    => 'Operaciones DEX',
            'contact_phone'   => '+591 71000001',
            'has_cod_service' => true,
            'is_active'       => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/carriers');

        $response->assertStatus(200);
        $response->assertSee('Transportadoras & Coberturas COD Bolivia');
        $response->assertSee('DEX Bolivia Express');
        $response->assertSee('dex_bolivia');
        $response->assertSee('Nueva Transportadora');
    }

    public function test_admin_can_create_new_carrier_and_it_appears_in_checkout(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/carriers', [
            'name'                     => 'Servicourier Bolivia',
            'code'                     => 'servicourier_bo',
            'contact_name'             => 'Lic. Mario Vargas',
            'contact_phone'            => '+591 78599412',
            'contact_email'            => 'contacto@servicourier.bo',
            'has_cod_service'          => '1',
            'default_shipping_scz_bob' => '17.50',
            'default_shipping_lpz_bob' => '24.00',
            'default_shipping_cbb_bob' => '21.00',
        ]);

        $response->assertSessionHas('success');

        // 1. Verificar creación de la transportadora en base de datos
        $this->assertDatabaseHas('carriers', [
            'tenant_id'       => $this->tenant->id,
            'name'            => 'Servicourier Bolivia',
            'code'            => 'servicourier_bo',
            'contact_name'    => 'Lic. Mario Vargas',
            'has_cod_service' => true,
            'is_active'       => true,
        ]);

        $carrier = Carrier::where('code', 'servicourier_bo')->first();

        // 2. Verificar que se crearon las coberturas para Santa Cruz, La Paz y Cochabamba
        $this->assertDatabaseHas('carrier_coverages', [
            'carrier_id'        => $carrier->id,
            'department'        => 'Santa Cruz',
            'base_shipping_bob' => 17.50,
        ]);
        $this->assertDatabaseHas('carrier_coverages', [
            'carrier_id'        => $carrier->id,
            'department'        => 'La Paz',
            'base_shipping_bob' => 24.00,
        ]);
        $this->assertDatabaseHas('carrier_coverages', [
            'carrier_id'        => $carrier->id,
            'department'        => 'Cochabamba',
            'base_shipping_bob' => 21.00,
        ]);

        // 3. Verificar que aparece inmediatamente en la selección de Checkout COD (/checkout-demo)
        $checkoutResponse = $this->actingAs($this->dropshipper)->get('/checkout-demo');
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('Servicourier Bolivia');
        $checkoutResponse->assertSee('servicourier_bo');
    }

    public function test_admin_can_update_carrier_and_toggle_status(): void
    {
        $carrier = Carrier::create([
            'tenant_id'       => $this->tenant->id,
            'name'            => 'Transportes El Rápido',
            'code'            => 'el_rapido',
            'contact_phone'   => '+591 70000000',
            'has_cod_service' => true,
            'is_active'       => true,
        ]);

        // Actualizar
        $updateResponse = $this->actingAs($this->admin)->put("/admin/carriers/{$carrier->id}", [
            'name'            => 'El Rápido Express Bolivia',
            'contact_name'    => 'Supervisión El Rápido',
            'contact_phone'   => '+591 79999999',
            'has_cod_service' => '1',
            'is_active'       => '1',
        ]);
        $updateResponse->assertSessionHas('success');

        $carrier->refresh();
        $this->assertEquals('El Rápido Express Bolivia', $carrier->name);
        $this->assertEquals('+591 79999999', $carrier->contact_phone);

        // Desactivar (Toggle)
        $toggleResponse = $this->actingAs($this->admin)->post("/admin/carriers/{$carrier->id}/toggle-status");
        $toggleResponse->assertSessionHas('success');

        $carrier->refresh();
        $this->assertFalse($carrier->is_active);

        // Al estar inactivo, NO debe aparecer como opción en el selector del checkout
        $this->flushSession();
        $checkoutResponse = $this->actingAs($this->dropshipper)->get('/checkout-demo');
        $checkoutResponse->assertDontSee('<option value="el_rapido">', false);
    }

    public function test_admin_can_add_and_delete_carrier_coverage(): void
    {
        $carrier = Carrier::create([
            'tenant_id'       => $this->tenant->id,
            'name'            => 'Courier Tarija Express',
            'code'            => 'tarija_express',
            'has_cod_service' => true,
            'is_active'       => true,
        ]);

        // Agregar Cobertura
        $covResponse = $this->actingAs($this->admin)->post("/admin/carriers/{$carrier->id}/coverages", [
            'department'              => 'Tarija',
            'city'                    => 'Tarija',
            'base_shipping_bob'       => '28.00',
            'estimated_delivery_days' => 2,
            'zone_type'               => 'urban',
        ]);
        $covResponse->assertSessionHas('success');

        $this->assertDatabaseHas('carrier_coverages', [
            'carrier_id'        => $carrier->id,
            'department'        => 'Tarija',
            'city'              => 'Tarija',
            'base_shipping_bob' => 28.00,
        ]);

        $coverage = CarrierCoverage::where('carrier_id', $carrier->id)->first();

        // Eliminar Cobertura
        $delResponse = $this->actingAs($this->admin)->delete("/admin/carriers/coverages/{$coverage->id}");
        $delResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('carrier_coverages', [
            'id' => $coverage->id,
        ]);
    }
}
