<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Models\WarehouseSellerReserve;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WarehouseModuleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $provider;
    private User $dropshipper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'code'         => 'BOLI-TEST',
            'company_name' => 'Bolidrop Test SA',
            'legal_nit'    => '1029384019',
            'country_code' => 'BO',
            'currency_code'=> 'BOB',
            'is_active'    => true,
        ]);

        $this->provider = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Proveedor Test',
            'email'     => 'provider@test.com',
            'password'  => bcrypt('secret'),
            'role'      => 'provider',
            'is_active' => true,
        ]);

        $this->dropshipper = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Vendedor Test',
            'email'     => 'vendedor@test.com',
            'password'  => bcrypt('secret'),
            'role'      => 'dropshipper',
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function makeWarehouse(array $overrides = []): Warehouse
    {
        return Warehouse::create(array_merge([
            'tenant_id'   => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'name'        => 'Bodega SCZ Test',
            'code'        => 'WH-SCZ-TEST',
            'department'  => 'Santa Cruz',
            'city'        => 'Santa Cruz de la Sierra',
            'address'     => 'Av. Principal #100',
            'is_active'   => true,
        ], $overrides));
    }

    private function makeProduct(): Product
    {
        $cat = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Test Cat',
            'slug'      => 'test-cat',
        ]);

        return Product::create([
            'tenant_id'           => $this->tenant->id,
            'provider_id'         => $this->provider->id,
            'category_id'         => $cat->id,
            'name'                => 'Producto Test',
            'slug'                => 'producto-test-9999',
            'sku'                 => 'TEST-SKU-001',
            'cost_price_bob'      => 100,
            'suggested_price_bob' => 180,
            'min_price_bob'       => 120,
            'status'              => 'published',
        ]);
    }

    private function makeInventory(Warehouse $wh, Product $product, array $overrides = []): WarehouseInventory
    {
        return WarehouseInventory::create(array_merge([
            'tenant_id'       => $this->tenant->id,
            'warehouse_id'    => $wh->id,
            'product_id'      => $product->id,
            'stock_physical'  => 100,
            'stock_reserved'  => 0,
            'stock_available' => 100,
            'visible_public'  => true,
            'visible_sellers' => true,
        ], $overrides));
    }

    // =========================================================================
    // TEST 1 — LISTAR ALMACENES
    // =========================================================================
    public function test_provider_can_list_own_warehouses(): void
    {
        $this->makeWarehouse();
        Sanctum::actingAs($this->provider);

        $response = $this->getJson('/warehouses');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    // =========================================================================
    // TEST 2 — CREAR ALMACÉN
    // =========================================================================
    public function test_provider_can_create_warehouse(): void
    {
        Sanctum::actingAs($this->provider);

        $response = $this->postJson('/warehouses', [
            'name'       => 'Nueva Bodega LPZ',
            'code'       => 'WH-LPZ-TEST',
            'department' => 'La Paz',
            'city'       => 'El Alto',
            'address'    => 'Av. 6 de Marzo #200',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.code', 'WH-LPZ-TEST');

        $this->assertDatabaseHas('warehouses', ['code' => 'WH-LPZ-TEST']);
    }

    // =========================================================================
    // TEST 3 — Código duplicado debe fallar
    // =========================================================================
    public function test_cannot_create_warehouse_with_duplicate_code(): void
    {
        $this->makeWarehouse(['code' => 'WH-DUP-01']);
        Sanctum::actingAs($this->provider);

        $response = $this->postJson('/warehouses', [
            'name'       => 'Otra Bodega',
            'code'       => 'WH-DUP-01',
            'department' => 'Santa Cruz',
            'city'       => 'Santa Cruz',
            'address'    => 'Av. Test #1',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    // =========================================================================
    // TEST 4 — ACTUALIZAR ALMACÉN
    // =========================================================================
    public function test_provider_can_update_own_warehouse(): void
    {
        $wh = $this->makeWarehouse();
        Sanctum::actingAs($this->provider);

        $response = $this->putJson("/warehouses/{$wh->id}", [
            'name'       => 'Bodega Actualizada',
            'department' => 'Santa Cruz',
            'city'       => 'Warnes',
            'address'    => 'Carretera al Norte Km 15',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.city', 'Warnes');
    }

    // =========================================================================
    // TEST 5 — ELIMINAR ALMACÉN sin stock
    // =========================================================================
    public function test_provider_can_delete_warehouse_with_no_stock(): void
    {
        $wh = $this->makeWarehouse(['code' => 'WH-DEL-01']);
        Sanctum::actingAs($this->provider);

        $response = $this->deleteJson("/warehouses/{$wh->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('warehouses', ['id' => $wh->id]);
    }

    // =========================================================================
    // TEST 6 — No puede eliminar almacén CON stock
    // =========================================================================
    public function test_cannot_delete_warehouse_with_active_stock(): void
    {
        $wh      = $this->makeWarehouse(['code' => 'WH-NOSTOCK-01']);
        $product = $this->makeProduct();
        $this->makeInventory($wh, $product, ['stock_available' => 50]);

        Sanctum::actingAs($this->provider);

        $response = $this->deleteJson("/warehouses/{$wh->id}");

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('warehouses', ['id' => $wh->id]);
    }

    // =========================================================================
    // TEST 7 — LISTAR PRODUCTOS DEL ALMACÉN
    // =========================================================================
    public function test_provider_can_list_warehouse_products(): void
    {
        $wh      = $this->makeWarehouse();
        $product = $this->makeProduct();
        $this->makeInventory($wh, $product);

        Sanctum::actingAs($this->provider);

        $response = $this->getJson("/warehouses/{$wh->id}/products");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $items = $response->json('data.data') ?? $response->json('data');
        $this->assertCount(1, $items);
    }

    // =========================================================================
    // TEST 8 — TOGGLE VISIBILIDAD INDIVIDUAL
    // =========================================================================
    public function test_provider_can_toggle_visibility_of_product_in_warehouse(): void
    {
        $wh      = $this->makeWarehouse();
        $product = $this->makeProduct();
        $inv     = $this->makeInventory($wh, $product, ['visible_public' => true]);

        Sanctum::actingAs($this->provider);

        $response = $this->patchJson("/warehouses/{$wh->id}/products/{$inv->id}/visibility", [
            'visible_public' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('warehouse_inventories', [
            'id'             => $inv->id,
            'visible_public' => false,
        ]);
    }

    // =========================================================================
    // TEST 9 — TOGGLE EN LOTE
    // =========================================================================
    public function test_provider_can_batch_toggle_visibility(): void
    {
        $wh = $this->makeWarehouse();
        $p1 = $this->makeProduct();
        // necesitamos un segundo producto con SKU diferente
        $p2 = Product::create([
            'tenant_id'           => $this->tenant->id,
            'provider_id'         => $this->provider->id,
            'category_id'         => $p1->category_id,
            'name'                => 'Producto 2',
            'slug'                => 'producto-2-9999',
            'sku'                 => 'TEST-SKU-002',
            'cost_price_bob'      => 80,
            'suggested_price_bob' => 150,
            'min_price_bob'       => 100,
            'status'              => 'published',
        ]);
        $inv1 = $this->makeInventory($wh, $p1, ['visible_sellers' => true]);
        $inv2 = $this->makeInventory($wh, $p2, ['visible_sellers' => true]);

        Sanctum::actingAs($this->provider);

        $response = $this->patchJson("/warehouses/{$wh->id}/products/batch-visibility", [
            'inventory_ids'   => [$inv1->id, $inv2->id],
            'visible_sellers' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('warehouse_inventories', ['id' => $inv1->id, 'visible_sellers' => false]);
        $this->assertDatabaseHas('warehouse_inventories', ['id' => $inv2->id, 'visible_sellers' => false]);
    }

    // =========================================================================
    // TEST 10 — CREAR RESERVA DE STOCK
    // =========================================================================
    public function test_provider_can_reserve_stock_for_dropshipper(): void
    {
        $wh      = $this->makeWarehouse();
        $product = $this->makeProduct();
        $inv     = $this->makeInventory($wh, $product, ['stock_available' => 100, 'stock_reserved' => 0]);

        Sanctum::actingAs($this->provider);

        $response = $this->postJson("/warehouses/{$wh->id}/reserves", [
            'product_id'     => $product->id,
            'dropshipper_id' => $this->dropshipper->id,
            'quantity'       => 20,
            'notes'          => 'Reserva de prueba para campaña navideña',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        // Verificar que el stock fue descontado
        $this->assertDatabaseHas('warehouse_inventories', [
            'id'              => $inv->id,
            'stock_available' => 80,
            'stock_reserved'  => 20,
        ]);

        // Verificar que se creó el registro de reserva
        $this->assertDatabaseHas('warehouse_seller_reserves', [
            'warehouse_id'   => $wh->id,
            'product_id'     => $product->id,
            'dropshipper_id' => $this->dropshipper->id,
            'quantity'       => 20,
            'status'         => 'active',
        ]);
    }

    // =========================================================================
    // TEST 11 — No puede reservar más del disponible
    // =========================================================================
    public function test_cannot_reserve_more_than_available_stock(): void
    {
        $wh      = $this->makeWarehouse();
        $product = $this->makeProduct();
        $this->makeInventory($wh, $product, ['stock_available' => 5]);

        Sanctum::actingAs($this->provider);

        $response = $this->postJson("/warehouses/{$wh->id}/reserves", [
            'product_id'     => $product->id,
            'dropshipper_id' => $this->dropshipper->id,
            'quantity'       => 100,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    // =========================================================================
    // TEST 12 — LIBERAR RESERVA devuelve stock
    // =========================================================================
    public function test_provider_can_release_reserve_and_stock_returns(): void
    {
        $wh      = $this->makeWarehouse();
        $product = $this->makeProduct();
        $inv     = $this->makeInventory($wh, $product, ['stock_available' => 80, 'stock_reserved' => 20]);

        $reserve = WarehouseSellerReserve::create([
            'tenant_id'      => $this->tenant->id,
            'warehouse_id'   => $wh->id,
            'product_id'     => $product->id,
            'dropshipper_id' => $this->dropshipper->id,
            'reserved_by'    => $this->provider->id,
            'quantity'       => 20,
            'status'         => 'active',
        ]);

        Sanctum::actingAs($this->provider);

        $response = $this->patchJson("/warehouses/{$wh->id}/reserves/{$reserve->id}/release");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // Stock disponible restaurado
        $this->assertDatabaseHas('warehouse_inventories', [
            'id'              => $inv->id,
            'stock_available' => 100,
            'stock_reserved'  => 0,
        ]);

        // Reserva marcada como liberada
        $this->assertDatabaseHas('warehouse_seller_reserves', [
            'id'     => $reserve->id,
            'status' => 'released',
        ]);
    }

    // =========================================================================
    // TEST 13 — Dropshipper NO puede crear almacenes
    // =========================================================================
    public function test_dropshipper_cannot_create_warehouse(): void
    {
        Sanctum::actingAs($this->dropshipper);

        $response = $this->postJson('/warehouses', [
            'name'       => 'Bodega Ilegal',
            'code'       => 'WH-ILEGAL-01',
            'department' => 'La Paz',
            'city'       => 'La Paz',
            'address'    => 'Calle Ilegal #0',
        ]);

        $response->assertStatus(403);
    }
}
