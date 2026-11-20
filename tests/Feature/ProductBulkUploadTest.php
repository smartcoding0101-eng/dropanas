<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryKardex;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductBulkUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $provider;
    protected User $dropshipper;
    protected Warehouse $warehouse;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);

        $this->provider = User::where('role', 'provider')->first();
        $this->dropshipper = User::where('role', 'dropshipper')->first();
        $this->warehouse = Warehouse::where('code', 'WH-SCZ-01')->first() ?? Warehouse::first();
        $this->category = Category::where('slug', 'tecnologia-y-gadgets')->first() ?? Category::first();
    }

    public function test_can_download_excel_template(): void
    {
        Sanctum::actingAs($this->provider);

        $response = $this->get('/products/template/download?format=xlsx');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_can_download_csv_template(): void
    {
        Sanctum::actingAs($this->provider);

        $response = $this->get('/products/template/download?format=csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_dropshipper_cannot_bulk_upload_products(): void
    {
        Sanctum::actingAs($this->dropshipper);

        $csvContent = "SKU;Nombre;Categoría;Costo;Min;Sugerido;Peso;Bodega;Stock;COD;Descripcion\n";
        $csvContent .= "TEST-01;Producto Test;Tecnología y Gadgets;50;60;90;1;WH-SCZ-01;10;SI;Desc\n";

        $file = UploadedFile::fake()->createWithContent('productos.csv', $csvContent);

        $response = $this->postJson('/products/bulk-upload', [
            'file' => $file,
        ]);

        $response->assertStatus(403);
    }

    public function test_provider_can_bulk_upload_products_via_csv(): void
    {
        Sanctum::actingAs($this->provider);

        $csvContent = "SKU;Nombre;Categoría;Costo;Min;Sugerido;Peso;Bodega;Stock;COD;Descripcion\n";
        $csvContent .= "BULK-EPSON-01;Impresora EcoTank Test;Tecnología y Gadgets;120.00;140.00;199.00;4.5;WH-SCZ-01;25;SI;Impresora alta calidad\n";
        $csvContent .= "BULK-AUD-02;Auriculares Bluetooth Test;Tecnología y Gadgets;35.50;50.00;80.00;0.3;WH-SCZ-01;50;SI;Auriculares estuche pro\n";

        $file = UploadedFile::fake()->createWithContent('productos.csv', $csvContent);

        $response = $this->postJson('/products/bulk-upload', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'created' => 2,
                    'updated' => 0,
                    'failed'  => 0,
                ],
            ]);

        // Verificar que los productos se crearon con Códigos Internos en paralelo
        $prod1 = Product::where('sku', 'BULK-EPSON-01')->first();
        $this->assertNotNull($prod1);
        $this->assertNotEmpty($prod1->internal_code);
        $this->assertStringStartsWith('BLD-PRD-', $prod1->internal_code);
        $this->assertEquals(120.00, (float) $prod1->cost_price_bob);
        $this->assertTrue($prod1->is_cod_available);

        $prod2 = Product::where('sku', 'BULK-AUD-02')->first();
        $this->assertNotNull($prod2);
        $this->assertNotEmpty($prod2->internal_code);
        $this->assertStringStartsWith('BLD-PRD-', $prod2->internal_code);
        $this->assertNotEquals($prod1->internal_code, $prod2->internal_code);

        // Verificar inventario físico asignado
        $inv1 = WarehouseInventory::where('product_id', $prod1->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertNotNull($inv1);
        $this->assertEquals(25, $inv1->stock_available);

        // Verificar registro de Kardex
        $kardex1 = InventoryKardex::where('product_id', $prod1->id)->first();
        $this->assertNotNull($kardex1);
        $this->assertEquals(25, $kardex1->quantity);
    }

    public function test_updating_existing_sku_retains_internal_code(): void
    {
        Sanctum::actingAs($this->provider);

        // 1. Crear producto inicial
        $csvContent1 = "SKU;Nombre;Categoría;Costo;Min;Sugerido;Peso;Bodega;Stock;COD;Descripcion\n";
        $csvContent1 .= "RETAIN-SKU-99;Parlante Inteligente Inicial;Tecnología y Gadgets;100;120;180;1.2;WH-SCZ-01;10;SI;Desc 1\n";

        $file1 = UploadedFile::fake()->createWithContent('import1.csv', $csvContent1);
        $this->postJson('/products/bulk-upload', ['file' => $file1]);

        $initialProduct = Product::where('sku', 'RETAIN-SKU-99')->first();
        $this->assertNotNull($initialProduct);
        $initialInternalCode = $initialProduct->internal_code;

        // 2. Re-importar el mismo SKU con precio actualizado
        $csvContent2 = "SKU;Nombre;Categoría;Costo;Min;Sugerido;Peso;Bodega;Stock;COD;Descripcion\n";
        $csvContent2 .= "RETAIN-SKU-99;Parlante Inteligente Actualizado;Tecnología y Gadgets;105;130;195;1.2;WH-SCZ-01;15;SI;Desc Actualizada\n";

        $file2 = UploadedFile::fake()->createWithContent('import2.csv', $csvContent2);
        $response = $this->postJson('/products/bulk-upload', ['file' => $file2]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'created' => 0,
                    'updated' => 1,
                    'failed'  => 0,
                ],
            ]);

        $updatedProduct = Product::where('sku', 'RETAIN-SKU-99')->first();
        $this->assertEquals($initialInternalCode, $updatedProduct->internal_code);
        $this->assertEquals('Parlante Inteligente Actualizado', $updatedProduct->name);
        $this->assertEquals(105.00, (float) $updatedProduct->cost_price_bob);
    }

    public function test_provider_can_bulk_upload_products_via_xlsx(): void
    {
        Sanctum::actingAs($this->provider);

        // Generar la plantilla oficial en XLSX (que ya contiene 3 productos de ejemplo)
        $service = app(\App\Services\ProductBulkImportService::class);
        $templateData = $service->generateTemplate('xlsx');

        $uploadedFile = new UploadedFile(
            $templateData['path'],
            'plantilla.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->postJson('/products/bulk-upload', [
            'file' => $uploadedFile,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'created' => 3,
                    'updated' => 0,
                    'failed'  => 0,
                ],
            ]);

        // Verificar que los 3 productos de ejemplo tienen su Código Interno
        $epson = Product::where('sku', 'IMPR-EPSON-L4150')->first();
        $this->assertNotNull($epson);
        $this->assertStringStartsWith('BLD-PRD-', $epson->internal_code);
        $this->assertEquals(120.00, (float) $epson->cost_price_bob);

        $aud = Product::where('sku', 'AUD-PRO-X9')->first();
        $this->assertNotNull($aud);
        $this->assertStringStartsWith('BLD-PRD-', $aud->internal_code);

        $sarten = Product::where('sku', 'SET-SARTEN-GRAN')->first();
        $this->assertNotNull($sarten);
        $this->assertStringStartsWith('BLD-PRD-', $sarten->internal_code);
    }
}
