<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProveedorProductoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    /** Proveedor publica un nuevo producto en el catalogo mayorista */
    public function test_provider_can_publish_new_product(): void
    {
        $provider = User::where('role', 'provider')->first();
        $category = Category::first();

        Sanctum::actingAs($provider);

        $response = $this->postJson('/api/v1/products', [
            'name'                => 'Auriculares Inalambricos TWS Pro',
            'sku'                 => 'TWS-PRO-001',
            'category_id'        => $category->id,
            'cost_price_bob'     => 80.00,
            'suggested_price_bob' => 149.00,
            'min_price_bob'      => 95.00,
            'is_cod_available'   => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'sku'         => 'TWS-PRO-001',
            'provider_id' => $provider->id,
            'status'      => 'published',
        ]);
    }

    /** Proveedor asigna inventario inicial en bodega al publicar */
    public function test_provider_can_assign_initial_warehouse_stock(): void
    {
        $provider  = User::where('role', 'provider')->first();
        $category  = Category::first();
        $warehouse = Warehouse::where('provider_id', $provider->id)->first();

        Sanctum::actingAs($provider);

        $response = $this->postJson('/api/v1/products', [
            'name'                 => 'Batidora Portatil Recargable',
            'sku'                  => 'BAT-PORT-01',
            'category_id'          => $category->id,
            'cost_price_bob'       => 55.00,
            'suggested_price_bob'  => 99.00,
            'min_price_bob'        => 65.00,
            'is_cod_available'     => true,
            'initial_warehouse_id' => $warehouse->id,
            'initial_stock'        => 30,
        ]);

        $response->assertStatus(201);

        $product = Product::where('sku', 'BAT-PORT-01')->first();
        $this->assertNotNull($product);

        $inventory = WarehouseInventory::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($inventory);
        $this->assertEquals(30, $inventory->stock_available);
        $this->assertEquals(30, $inventory->stock_physical);
    }

    /** No se puede publicar con precio sugerido menor que el costo */
    public function test_cannot_publish_product_with_suggested_price_below_cost(): void
    {
        $provider = User::where('role', 'provider')->first();
        $category = Category::first();

        Sanctum::actingAs($provider);

        $response = $this->postJson('/api/v1/products', [
            'name'                => 'Producto Invalido Precio',
            'sku'                 => 'INV-PRICE-01',
            'category_id'        => $category->id,
            'cost_price_bob'     => 100.00,
            'suggested_price_bob' => 80.00,
            'min_price_bob'      => 100.00,
            'is_cod_available'   => true,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('products', ['sku' => 'INV-PRICE-01']);
    }

    /** Dropshipper no puede publicar productos (rol incorrecto — 403) */
    public function test_dropshipper_cannot_publish_products(): void
    {
        $dropshipper = User::where('role', 'dropshipper')->first();
        $category    = Category::first();

        Sanctum::actingAs($dropshipper);

        $response = $this->postJson('/api/v1/products', [
            'name'                => 'Producto No Autorizado',
            'sku'                 => 'NO-AUTH-01',
            'category_id'        => $category->id,
            'cost_price_bob'     => 50.00,
            'suggested_price_bob' => 90.00,
            'min_price_bob'      => 55.00,
            'is_cod_available'   => true,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', ['sku' => 'NO-AUTH-01']);
    }

    /** Panel web del proveedor muestra boton de publicacion */
    public function test_proveedor_panel_shows_publish_button_and_categories(): void
    {
        $response = $this->get('/panel/proveedor');

        $response->assertStatus(200)
            ->assertSee('Publicar Nuevo Producto')
            ->assertSee('Mis Productos Publicados')
            ->assertSee('Mis Bodegas de Despacho');
    }

    /** Panel admin muestra seccion de manifiestos DEX */
    public function test_admin_panel_shows_manifest_section(): void
    {
        $response = $this->get('/panel/admin');

        $response->assertStatus(200)
            ->assertSee('Manifiestos de Carga Emitidos')
            ->assertSee('Generar Manifiesto DEX');
    }
}

