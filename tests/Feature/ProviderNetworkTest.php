<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Models\WarehouseSellerReserve;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProviderNetworkTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $provider;
    private User $dropshipper;
    private Customer $customer;
    private Warehouse $warehouse;
    private Product $product;
    private WarehouseInventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'code'         => 'BOLI-NET-TEST',
            'company_name' => 'Bolidrop Red Test',
            'legal_nit'    => '1029384019',
            'country_code' => 'BO',
            'currency_code'=> 'BOB',
            'is_active'    => true,
        ]);

        $this->provider = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Mayorista Tech Bolivia',
            'email'     => 'mayorista@test.com',
            'password'  => bcrypt('secret'),
            'role'      => 'provider',
            'is_active' => true,
        ]);

        $this->dropshipper = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Juan Dropshipper',
            'email'     => 'juan@tienda.com',
            'company_or_store_name' => 'MegaStore Bolivia',
            'password'  => bcrypt('secret'),
            'role'      => 'dropshipper',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'tenant_id'        => $this->tenant->id,
            'full_name'        => 'Mario Morales',
            'phone'            => '77001122',
            'email'            => 'mario@gmail.com',
            'customer_segment' => 'vip',
            'total_spent_bob'  => 1500,
        ]);

        $this->warehouse = Warehouse::create([
            'tenant_id'   => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'name'        => 'Bodega Central SCZ',
            'code'        => 'WH-SCZ-NET',
            'department'  => 'Santa Cruz',
            'city'        => 'Santa Cruz',
            'address'     => 'Zona Este',
            'is_active'   => true,
        ]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'General',
            'slug'      => 'general-net',
        ]);

        $this->product = Product::create([
            'tenant_id'           => $this->tenant->id,
            'provider_id'         => $this->provider->id,
            'category_id'         => $category->id,
            'name'                => 'Impresora Epson L3210',
            'slug'                => 'impresora-epson-l3210',
            'sku'                 => 'EPS-L3210',
            'cost_price_bob'      => 1200,
            'suggested_price_bob' => 1700,
            'min_price_bob'       => 1500,
            'status'              => 'published',
        ]);

        $this->inventory = WarehouseInventory::create([
            'tenant_id'       => $this->tenant->id,
            'warehouse_id'    => $this->warehouse->id,
            'product_id'      => $this->product->id,
            'stock_physical'  => 50,
            'stock_reserved'  => 0,
            'stock_available' => 50,
        ]);
    }

    public function test_provider_can_list_sellers(): void
    {
        Sanctum::actingAs($this->provider);

        $res = $this->getJson('/provider/network/sellers');
        $res->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertCount(1, $res->json('data.data'));
    }

    public function test_provider_can_list_and_create_customers(): void
    {
        Sanctum::actingAs($this->provider);

        $res = $this->getJson('/provider/network/customers');
        $res->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertCount(1, $res->json('data.data'));

        // Crear nuevo cliente
        $createRes = $this->postJson('/provider/network/customers', [
            'full_name'  => 'Laura Vargas',
            'phone'      => '71234567',
            'city'       => 'Cochabamba',
            'department' => 'Cochabamba',
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.full_name', 'Laura Vargas');

        $this->assertDatabaseHas('customers', ['phone' => '71234567']);
    }

    public function test_provider_can_reserve_stock_for_seller(): void
    {
        Sanctum::actingAs($this->provider);

        $res = $this->postJson('/provider/network/reserves', [
            'target_type'    => 'seller',
            'dropshipper_id' => $this->dropshipper->id,
            'warehouse_id'   => $this->warehouse->id,
            'product_id'     => $this->product->id,
            'quantity'       => 10,
            'notes'          => 'Apartado especial dropshipper',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('status', 'success');

        // Stock descontado de available
        $this->assertDatabaseHas('warehouse_inventories', [
            'id'              => $this->inventory->id,
            'stock_available' => 40,
            'stock_reserved'  => 10,
        ]);

        $this->assertDatabaseHas('warehouse_seller_reserves', [
            'target_type'    => 'seller',
            'dropshipper_id' => $this->dropshipper->id,
            'quantity'       => 10,
            'status'         => 'active',
        ]);
    }

    public function test_provider_can_reserve_stock_for_customer(): void
    {
        Sanctum::actingAs($this->provider);

        $res = $this->postJson('/provider/network/reserves', [
            'target_type'  => 'customer',
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id'   => $this->product->id,
            'quantity'     => 5,
            'notes'        => 'Apartado cliente mayorista',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('warehouse_inventories', [
            'id'              => $this->inventory->id,
            'stock_available' => 45,
            'stock_reserved'  => 5,
        ]);

        $this->assertDatabaseHas('warehouse_seller_reserves', [
            'target_type' => 'customer',
            'customer_id' => $this->customer->id,
            'quantity'    => 5,
            'status'      => 'active',
        ]);
    }

    public function test_provider_can_release_network_reserve(): void
    {
        Sanctum::actingAs($this->provider);

        // Crear reserva previa
        $reserve = WarehouseSellerReserve::create([
            'tenant_id'   => $this->tenant->id,
            'warehouse_id'=> $this->warehouse->id,
            'product_id'  => $this->product->id,
            'customer_id' => $this->customer->id,
            'target_type' => 'customer',
            'reserved_by' => $this->provider->id,
            'quantity'    => 15,
            'status'      => 'active',
        ]);

        $this->inventory->update(['stock_available' => 35, 'stock_reserved' => 15]);

        $res = $this->patchJson("/provider/network/reserves/{$reserve->id}/release");
        $res->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('warehouse_inventories', [
            'id'              => $this->inventory->id,
            'stock_available' => 50,
            'stock_reserved'  => 0,
        ]);

        $this->assertDatabaseHas('warehouse_seller_reserves', [
            'id'     => $reserve->id,
            'status' => 'released',
        ]);
    }
}
