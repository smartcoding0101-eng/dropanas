<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\Finance\CommissionEngineService;
use App\Services\Finance\SettlementConciliationService;
use App\Services\Logistics\ReverseLogisticsService;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Prueba de Carga Masiva de Registros, Auditoría de Rendimiento y Conciliación Global
 * Carga de decenas de productos, pedidos con múltiples ítems, novedades, entregas y liquidaciones.
 */
class MassiveDataLoadingAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $provider;
    protected User $dropshipper;
    protected Warehouse $warehouseSCZ;
    protected Warehouse $warehouseLPZ;
    protected Carrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);

        $this->tenant = Tenant::first();
        $this->admin = User::where('role', 'tenant_admin')->first();
        $this->provider = User::where('role', 'provider')->first();
        $this->dropshipper = User::where('role', 'dropshipper')->first();
        $this->warehouseSCZ = Warehouse::where('code', 'WH-SCZ-01')->first() ?? Warehouse::first();
        $this->warehouseLPZ = Warehouse::where('code', 'WH-LPZ-01')->first() ?? Warehouse::skip(1)->first();
        $this->carrier = Carrier::first();
    }

    /**
     * CARGA MASIVA 1: Ingesta de 10 productos y 40 pedidos multidepartamentales
     * con simulación de entregas, devoluciones y verificación de tiempos de respuesta
     */
    public function test_massive_dataset_ingestion_and_full_audit_validation(): void
    {
        $startTime = microtime(true);
        $category = Category::first();

        // 1. Crear 10 productos con inventario distribuido
        $createdProducts = [];
        for ($p = 1; $p <= 10; $p++) {
            $cost = 50.00 + ($p * 5);
            $product = Product::create([
                'tenant_id'           => $this->tenant->id,
                'provider_id'         => $this->provider->id,
                'category_id'         => $category->id,
                'name'                => "Producto Masivo {$p}",
                'slug'                => "prod-masivo-{$p}-" . uniqid(),
                'sku'                 => "SKU-MASS-{$p}",
                'cost_price_bob'      => $cost,
                'suggested_price_bob' => $cost * 2,
                'min_price_bob'       => $cost * 1.3,
                'status'              => 'published',
                'is_cod_available'    => true,
            ]);

            WarehouseInventory::create([
                'tenant_id'       => $this->tenant->id,
                'warehouse_id'    => $this->warehouseSCZ->id,
                'product_id'      => $product->id,
                'stock_physical'  => 100,
                'stock_reserved'  => 0,
                'stock_available' => 100,
            ]);

            WarehouseInventory::create([
                'tenant_id'       => $this->tenant->id,
                'warehouse_id'    => $this->warehouseLPZ->id,
                'product_id'      => $product->id,
                'stock_physical'  => 80,
                'stock_reserved'  => 0,
                'stock_available' => 80,
            ]);

            $createdProducts[] = $product;
        }

        $this->assertCount(10, $createdProducts);

        // 2. Generar 40 pedidos con diferentes flujos (30 entregados y liquidados, 10 con devolución)
        $settlementService = app(SettlementConciliationService::class);
        $reverseLogistics = app(ReverseLogisticsService::class);
        $commissionEngine = app(CommissionEngineService::class);

        $totalDeliveredCod = 0.0;
        $totalDeliveredCost = 0.0;
        $totalDeliveredShipping = 0.0;
        $totalDeliveredPlatform = 0.0;
        $totalDeliveredProfit = 0.0;

        for ($i = 1; $i <= 40; $i++) {
            $product = $createdProducts[$i % count($createdProducts)];
            $warehouse = ($i % 2 === 0) ? $this->warehouseSCZ : $this->warehouseLPZ;
            $unitCost = (float) $product->cost_price_bob;
            $sellingPrice = (float) $product->suggested_price_bob;
            $shippingFee = 20.00;

            $order = Order::create([
                'tenant_id'             => $this->tenant->id,
                'dropshipper_id'        => $this->dropshipper->id,
                'warehouse_id'          => $warehouse->id,
                'carrier_id'            => $this->carrier->id,
                'order_number'          => "MASS-ORD-{$i}-" . uniqid(),
                'customer_name'         => "Destinatario {$i}",
                'customer_phone'        => "+591 7800" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'shipping_department'   => $warehouse->department,
                'shipping_city'         => $warehouse->city,
                'shipping_address'      => "Av. Principal #{$i}",
                'total_amount_bob'      => $sellingPrice,
                'cost_amount_bob'       => $unitCost,
                'shipping_fee_bob'      => $shippingFee,
                'platform_fee_bob'      => 0.00,
                'dropshipper_profit_bob'=> 0.00,
                'status'                => 'dispatched',
                'payment_method'        => 'cash_on_delivery_bob',
            ]);

            $order->items()->create([
                'tenant_id'     => $this->tenant->id,
                'product_id'    => $product->id,
                'provider_id'   => $this->provider->id,
                'quantity'      => 1,
                'unit_cost_bob' => $unitCost,
                'unit_price_bob'=> $sellingPrice,
                'subtotal_bob'  => $sellingPrice,
            ]);

            $order->load('items.product');

            $platformFee = $commissionEngine->calculatePlatformFee($order);
            $profit = max(0, $sellingPrice - $unitCost - $shippingFee - $platformFee);

            $order->update([
                'platform_fee_bob'      => $platformFee,
                'dropshipper_profit_bob'=> $profit,
            ]);

            if ($i <= 30) {
                // Pedidos 1 a 30: Entregados y liquidados
                $settlementService->settleDeliveredOrder($order);

                $totalDeliveredCod      += $sellingPrice;
                $totalDeliveredCost     += $unitCost;
                $totalDeliveredShipping += $shippingFee;
                $totalDeliveredPlatform += $platformFee;
                $totalDeliveredProfit   += $profit;
            } else {
                // Pedidos 31 a 40: Devoluciones por logística inversa
                $reverseLogistics->recordNovedad($order, 'cliente_ausente');
                $reverseLogistics->recordNovedad($order, 'telefono_apagado');
                $reverseLogistics->recordNovedad($order, 'rechazo_sin_dinero');
                $reverseLogistics->processReturn($order, deductShippingFeeFromDropshipper: true);
            }
        }

        $ingestionDuration = microtime(true) - $startTime;
        $this->assertLessThan(10.0, $ingestionDuration, "La ingesta masiva de 50 registros no debe exceder 10s (tomó {$ingestionDuration}s)");

        // 3. Ejecutar y validar el comando de conciliación de auditoría sobre toda la masa
        $exitCode = Artisan::call('audit:reconcile-ledgers');
        $this->assertEquals(0, $exitCode, 'La reconciliación contable debe pasar con éxito sobre toda la masa cargada');

        // 4. Probar rendimiento de APIs de consulta sobre la masa de datos
        Sanctum::actingAs($this->dropshipper);

        $queryStart = microtime(true);
        $resOrders = $this->getJson('/api/v1/orders');
        $queryDuration = (microtime(true) - $queryStart) * 1000;

        $resOrders->assertStatus(200);
        $this->assertLessThan(800, $queryDuration, "El listado de pedidos debe responder en menos de 800ms (tomó {$queryDuration}ms)");

        // 5. Verificar que el balance de las billeteras no tenga desvíos
        $walletDs = Wallet::where('user_id', $this->dropshipper->id)->first();
        $netDsLedger = (float) $walletDs->transactions()->sum('amount');
        $this->assertEquals((float) $walletDs->balance_available, $netDsLedger, 'El saldo del Dropshipper debe ser exactamente igual a la suma del ledger');
    }
}

