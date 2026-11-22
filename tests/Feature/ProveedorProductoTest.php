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
        $provider = User::where('role', 'provider')->first();
        $response = $this->actingAs($provider)->get('/panel/proveedor');

        $response->assertStatus(200)
            ->assertSee('Publicar Nuevo Producto')
            ->assertSee('Mis Productos Publicados')
            ->assertSee('Mis Bodegas de Despacho');
    }

    /** Panel admin muestra seccion de manifiestos DEX */
    public function test_admin_panel_shows_manifest_section(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $response = $this->actingAs($admin)->get('/panel/admin');

        $response->assertStatus(200)
            ->assertSee('Manifiestos de Carga Emitidos')
            ->assertSee('Generar Manifiesto DEX');
    }

    /** Proveedor puede cargar imagen física y visualizarla en catálogo */
    public function test_provider_can_upload_product_with_image_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $provider = User::where('role', 'provider')->first();
        $category = Category::first();

        Sanctum::actingAs($provider);

        $file = \Illuminate\Http\UploadedFile::fake()->image('reloj_deportivo.png', 400, 400);

        $response = $this->post('/api/v1/products', [
            'name'                => 'Reloj Smartwatch Pro Con Imagen',
            'sku'                 => 'WATCH-IMG-001',
            'category_id'         => $category->id,
            'cost_price_bob'      => 90.00,
            'suggested_price_bob' => 160.00,
            'min_price_bob'       => 110.00,
            'is_cod_available'    => true,
            'image'               => $file,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);

        $product = Product::where('sku', 'WATCH-IMG-001')->first();
        $this->assertNotNull($product);
        $this->assertNotEmpty($product->images);
        $this->assertStringContainsString('/storage/products/', $product->images[0]);
    }

    /** CRUD completo de Categorías de Productos (Listar, Insertar, Editar, Eliminar) */
    public function test_category_crud_list_create_update_and_delete(): void
    {
        $provider = User::where('role', 'provider')->first();
        $this->actingAs($provider);

        // 1. Insertar nueva categoría
        $createRes = $this->postJson('/categories', [
            'name'        => 'Deportes y Fitness',
            'description' => 'Equipos de entrenamiento y aire libre',
        ]);
        $createRes->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Deportes y Fitness');

        $catId = $createRes->json('data.id');
        $this->assertDatabaseHas('categories', ['id' => $catId, 'name' => 'Deportes y Fitness']);

        // 2. Listar categorías
        $listRes = $this->getJson('/categories');
        $listRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // 3. Editar categoría
        $updateRes = $this->putJson("/categories/{$catId}", [
            'name'        => 'Deportes, Fitness y Camping',
            'description' => 'Descripción actualizada',
        ]);
        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Deportes, Fitness y Camping');

        // 4. Intentar eliminar categoría que sí tiene productos (debe proteger integridad 422)
        $catWithProducts = Category::has('products')->first();
        $failDelete = $this->deleteJson("/categories/{$catWithProducts->id}");
        $failDelete->assertStatus(422);

        // 5. Eliminar categoría sin productos
        $deleteRes = $this->deleteJson("/categories/{$catId}");
        $deleteRes->assertStatus(200)
            ->assertJsonPath('status', 'success');
        $this->assertDatabaseMissing('categories', ['id' => $catId]);
    }

    /** Publicar producto vía formulario web (/products con FormData boolean string "true") aparece en Catálogo e Inventarios */
    public function test_web_product_creation_with_formdata_boolean_appears_in_catalog_and_inventory(): void
    {
        $provider  = User::where('role', 'provider')->first();
        $category  = Category::first();
        $warehouse = Warehouse::where('provider_id', $provider->id)->first();

        $response = $this->actingAs($provider)->postJson('/products', [
            'name'                 => 'Impresora Multifuncional Epson EcoTank L4150',
            'sku'                  => 'IMPR-EPSON-01',
            'category_id'          => $category->id,
            'cost_price_bob'       => 120,
            'min_price_bob'        => 140,
            'suggested_price_bob'  => 199,
            'initial_warehouse_id' => $warehouse->id,
            'initial_stock'        => 50,
            'is_cod_available'     => 'true', // Tal como lo envía FormData.append() en JS
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        // Verificar que aparece en /catalogo
        $catalogRes = $this->actingAs($provider)->get('/catalogo');
        $catalogRes->assertStatus(200)
            ->assertSee('Impresora Multifuncional Epson EcoTank L4150')
            ->assertSee('IMPR-EPSON-01');

        // Verificar que aparece en /panel/proveedor (Inventarios y Mis Productos)
        $panelRes = $this->actingAs($provider)->get('/panel/proveedor');
        $panelRes->assertStatus(200)
            ->assertSee('Impresora Multifuncional Epson EcoTank L4150')
            ->assertSee('IMPR-EPSON-01');
    }

    /** Proveedor puede obtener detalle de producto vía GET /products/{id} */
    public function test_provider_can_view_product_detail_json(): void
    {
        $provider = User::where('role', 'provider')->first();
        $product  = Product::where('provider_id', $provider->id)->first();

        $response = $this->actingAs($provider)->getJson("/products/{$product->id}");
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $product->id);
    }

    /** Proveedor puede editar su producto vía PUT /products/{id} o POST con _method=PUT */
    public function test_provider_can_update_product_via_web_route(): void
    {
        $provider = User::where('role', 'provider')->first();
        $product  = Product::where('provider_id', $provider->id)->first();
        $category = Category::first();

        $updateData = [
            'name'                => 'Impresora Canon G3180 Actualizada',
            'sku'                 => $product->sku,
            'category_id'         => $category->id,
            'cost_price_bob'      => 130.00,
            'suggested_price_bob' => 220.00,
            'min_price_bob'       => 150.00,
            'is_cod_available'    => true,
            'description'         => 'Nueva descripción detallada con garantía',
            'attributes'          => json_encode([
                'specs'         => ['Marca' => 'Canon', 'Modelo' => 'G3180'],
                'box_content'   => 'Caja original completa',
                'warranty_info' => '6 meses de garantía',
            ]),
        ];

        // 1. Vía PUT
        $response = $this->actingAs($provider)->putJson("/products/{$product->id}", $updateData);
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Impresora Canon G3180 Actualizada');

        $this->assertDatabaseHas('products', [
            'id'   => $product->id,
            'name' => 'Impresora Canon G3180 Actualizada',
        ]);

        // 2. Vía POST con _method = PUT (como lo hace FormData)
        $postData = array_merge($updateData, [
            '_method' => 'PUT',
            'name'    => 'Impresora Canon G3180 Editada Por FormData',
        ]);

        $postResponse = $this->actingAs($provider)->post("/products/{$product->id}", $postData, [
            'Accept' => 'application/json',
        ]);
        $postResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('products', [
            'id'   => $product->id,
            'name' => 'Impresora Canon G3180 Editada Por FormData',
        ]);
    }

    /** Proveedor puede eliminar producto sin inventario ni kardex activo */
    public function test_provider_can_delete_clean_product(): void
    {
        $provider = User::where('role', 'provider')->first();
        $category = Category::first();

        // Crear producto limpio sin stock
        $cleanProduct = Product::create([
            'tenant_id'           => $provider->tenant_id,
            'provider_id'         => $provider->id,
            'category_id'         => $category->id,
            'name'                => 'Producto Prueba Borrado',
            'slug'                => 'prod-test-del-' . uniqid(),
            'sku'                 => 'DEL-TEST-001',
            'cost_price_bob'      => 50.00,
            'suggested_price_bob' => 100.00,
            'min_price_bob'       => 60.00,
            'status'              => 'published',
            'is_cod_available'    => true,
        ]);

        $response = $this->actingAs($provider)->deleteJson("/products/{$cleanProduct->id}");
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('products', ['id' => $cleanProduct->id]);
    }
}


