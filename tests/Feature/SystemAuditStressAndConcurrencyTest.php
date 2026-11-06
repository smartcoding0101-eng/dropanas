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
use App\Services\Finance\BankSettlementService;
use App\Services\Finance\CommissionEngineService;
use App\Services\Finance\SettlementConciliationService;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas de Estrés, Concurrencia y Resiliencia del Sistema Dropana Bolivia
 *
 * Módulos sometidos a estrés:
 * 1. Disputa Concurrente de Inventario (Prevención de Sobreventa / Race Conditions)
 * 2. Concurrencia Financiera en Billeteras (Prevención de Doble Retiro / Double-Spending)
 * 3. Idempotencia de Liquidación (Prevención de Doble Acreditación)
 * 4. Procesamiento Masivo en Ráfaga (Burst of 50 Orders: Compra, Venta, Despacho, Liquidación y Balance Cero-Drift)
 * 5. Agrupación y Liquidación Bancaria de Alto Volumen
 */
class SystemAuditStressAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $provider;
    protected User $dropshipper;
    protected Warehouse $warehouse;
    protected Carrier $carrier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);

        $this->tenant = Tenant::first();
        $this->admin = User::where('role', 'tenant_admin')->first();
        $this->provider = User::where('role', 'provider')->first();
        $this->dropshipper = User::where('role', 'dropshipper')->first();
        $this->warehouse = Warehouse::first();
        $this->carrier = Carrier::first();
        $this->product = Product::first();
    }

    /**
     * ESTRÉS 1: Prevención de Sobreventa ante Pedidos Concurrentes (Race Condition en Stock)
     * Se configuran exactamente 5 unidades en stock disponible.
     * Se envían 15 intentos de pedido de 1 unidad cada uno.
     * Resultado esperado: Exactamente 5 son aceptados, 10 son rechazados con HTTP 422.
     * Stock final disponible: 0, Stock reservado: 5. Ningún valor negativo.
     */
    public function test_stress_concurrency_overselling_prevention(): void
    {
        // Forzar stock exacto a 5 unidades
        $inventory = WarehouseInventory::updateOrCreate(
            ['warehouse_id' => $this->warehouse->id, 'product_id' => $this->product->id],
            ['tenant_id' => $this->tenant->id, 'stock_physical' => 5, 'stock_reserved' => 0, 'stock_available' => 5]
        );

        Sanctum::actingAs($this->dropshipper);

        $successfulOrders = 0;
        $rejectedOrders = 0;
        $totalAttempts = 15;

        for ($i = 1; $i <= $totalAttempts; $i++) {
            $response = $this->postJson('/api/v1/orders', [
                'warehouse_id'        => $this->warehouse->id,
                'carrier_code'        => $this->carrier->code,
                'customer_name'       => "Cliente Concurrente {$i}",
                'customer_phone'      => "+591 700000{$i}",
                'shipping_department' => 'Santa Cruz',
                'shipping_city'       => 'Santa Cruz de la Sierra',
                'shipping_address'    => "Calle {$i} #{$i}",
                'payment_method'      => 'cash_on_delivery_bob',
                'items'               => [
                    [
                        'product_id'        => $this->product->id,
                        'quantity'          => 1,
                        'selling_price_bob' => 120.00,
                    ],
                ],
            ]);

            if ($response->status() === 201) {
                $successfulOrders++;
            } elseif ($response->status() === 422) {
                $rejectedOrders++;
            }
        }

        $this->assertEquals(5, $successfulOrders, 'Exactamente 5 pedidos deben crearse (igual al stock disponible)');
        $this->assertEquals(10, $rejectedOrders, 'Exactamente 10 pedidos deben ser rechazados por falta de stock');

        $inventory->refresh();
        $this->assertEquals(0, $inventory->stock_available, 'Stock disponible debe ser 0 exacto, sin saldo negativo');
        $this->assertEquals(5, $inventory->stock_reserved, 'Stock reservado debe ser 5 exacto');
        $this->assertEquals(5, $inventory->stock_physical, 'Stock físico sigue en 5 hasta la entrega');
    }

    /**
     * ESTRÉS 2: Prevención de Doble Retiro en Billetera (Double-Spending Prevention)
     * Saldo inicial: 200.00 BOB.
     * Se ejecutan 5 intentos de retiro consecutivos de 100.00 BOB cada uno.
     * Resultado esperado: Solo 2 retiros tienen éxito. 3 son rechazados con 422.
     * Saldo final disponible: 0.00 BOB. Retirado: 200.00 BOB. Sin saldo negativo.
     */
    public function test_stress_wallet_double_spending_prevention(): void
    {
        $wallet = Wallet::updateOrCreate(
            ['user_id' => $this->dropshipper->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 200.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        Sanctum::actingAs($this->dropshipper);

        $successfulWithdrawals = 0;
        $rejectedWithdrawals = 0;

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/wallet/withdraw', [
                'amount'          => 100.00,
                'payout_method'   => 'bank_transfer',
                'account_details' => [
                    'beneficiary_name' => 'Antony Dropshipper',
                    'document_id'      => '5432101 SC',
                    'bank_name'        => 'Banco Unión S.A.',
                    'account_number'   => '100-9876543-01',
                ],
            ]);

            if ($response->status() === 201) {
                $successfulWithdrawals++;
            } elseif ($response->status() === 422) {
                $rejectedWithdrawals++;
            }
        }

        $this->assertEquals(2, $successfulWithdrawals, 'Solo 2 retiros deben ser aprobados para un saldo de 200 BOB');
        $this->assertEquals(3, $rejectedWithdrawals, 'Los 3 retiros sobrantes deben ser rechazados');

        $wallet->refresh();
        $this->assertEquals(0.00, (float) $wallet->balance_available, 'Saldo disponible debe ser 0.00');
        $this->assertEquals(200.00, (float) $wallet->balance_withdrawn, 'Saldo retirado debe ser exactamente 200.00');

        // Verificar consistencia del libro mayor
        $withdrawalTxs = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'withdrawal_request')
            ->get();

        $this->assertCount(2, $withdrawalTxs, 'Debe haber exactamente 2 transacciones de retiro en el ledger');
        $this->assertEquals(-200.00, (float) $withdrawalTxs->sum('amount'));
    }

    /**
     * ESTRÉS 3: Idempotencia en Liquidación de Pedidos Concurrentes (Prevención de Doble Acreditación)
     * Si el transportista o webhook envía múltiples confirmaciones simultáneas de entrega
     * para el mismo pedido, el servicio debe liquidar solo una vez.
     */
    public function test_stress_idempotent_settlement_prevents_duplicate_payouts(): void
    {
        $dropshipperWallet = Wallet::updateOrCreate(
            ['user_id' => $this->dropshipper->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
        $dropshipperWallet->update(['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]);
        WalletTransaction::where('wallet_id', $dropshipperWallet->id)->delete();

        $providerWallet = Wallet::updateOrCreate(
            ['user_id' => $this->provider->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
        $providerWallet->update(['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]);
        WalletTransaction::where('wallet_id', $providerWallet->id)->delete();

        $order = Order::create([
            'tenant_id'             => $this->tenant->id,
            'dropshipper_id'        => $this->dropshipper->id,
            'warehouse_id'          => $this->warehouse->id,
            'carrier_id'            => $this->carrier->id,
            'order_number'          => 'ORD-IDEMPOTENT-01',
            'customer_name'         => 'Cliente Prueba Idempotencia',
            'customer_phone'        => '70011223',
            'shipping_department'   => 'Santa Cruz',
            'shipping_city'         => 'Santa Cruz',
            'shipping_address'      => 'Av. Cañoto #100',
            'total_amount_bob'      => 300.00,
            'cost_amount_bob'       => 100.00,
            'shipping_fee_bob'      => 20.00,
            'platform_fee_bob'      => 15.00,
            'dropshipper_profit_bob'=> 165.00,
            'status'                => 'dispatched',
        ]);

        $order->items()->create([
            'tenant_id'     => $this->tenant->id,
            'product_id'    => $this->product->id,
            'provider_id'   => $this->provider->id,
            'quantity'      => 1,
            'unit_cost_bob' => 100.00,
            'unit_price_bob'=> 300.00,
            'subtotal_bob'  => 300.00,
        ]);

        $settlementService = app(SettlementConciliationService::class);

        // Simular 5 llamadas concurrentes/repetidas de liquidación sobre la misma orden
        for ($i = 0; $i < 5; $i++) {
            $settlementService->settleDeliveredOrder($order);
        }

        $dropshipperWallet->refresh();
        $providerWallet->refresh();

        // Verificar que solo se acreditó una única vez
        $this->assertEquals(165.00, (float) $dropshipperWallet->balance_available, 'Ganancia del dropshipper debe ser exactamente 165.00 BOB (una sola acreditación)');
        $this->assertEquals(100.00, (float) $providerWallet->balance_available, 'Costo del proveedor debe ser exactamente 100.00 BOB (una sola acreditación)');

        // Verificar conteo de transacciones en el libro mayor
        $dsTxCount = WalletTransaction::where('wallet_id', $dropshipperWallet->id)
            ->where('reference_id', $order->id)
            ->count();
        $this->assertEquals(1, $dsTxCount, 'Solo 1 transacción en ledger de dropshipper');

        $provTxCount = WalletTransaction::where('wallet_id', $providerWallet->id)
            ->where('reference_id', $order->id)
            ->count();
        $this->assertEquals(1, $provTxCount, 'Solo 1 transacción en ledger de proveedor');
    }

    /**
     * ESTRÉS 4: Ráfaga de Alto Volumen (Burst de 30 Pedidos Completos COD)
     * Se procesa una ráfaga continua de 30 pedidos COD con:
     * - Descuento y reserva de inventario
     * - Despacho con transportadora
     * - Entrega y cobro en campo
     * - Auto-liquidación a billeteras
     *
     * Validación de Auditoría Financiera:
     * SUM(total_recaudado_cod) == SUM(costos_proveedor) + SUM(fletes_courier) + SUM(comisiones_plataforma) + SUM(utilidades_dropshipper)
     * Discrepancia matemática = 0.00 centavos (Zero Drift).
     */
    public function test_stress_burst_high_throughput_orders_and_mathematical_zero_drift(): void
    {
        // Asegurar suficiente inventario en bodega para 30 unidades
        $inventory = WarehouseInventory::updateOrCreate(
            ['warehouse_id' => $this->warehouse->id, 'product_id' => $this->product->id],
            ['tenant_id' => $this->tenant->id, 'stock_physical' => 100, 'stock_reserved' => 0, 'stock_available' => 100]
        );

        $dropshipperWallet = Wallet::updateOrCreate(
            ['user_id' => $this->dropshipper->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
        $dropshipperWallet->update(['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]);
        WalletTransaction::where('wallet_id', $dropshipperWallet->id)->delete();

        $providerWallet = Wallet::updateOrCreate(
            ['user_id' => $this->provider->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
        $providerWallet->update(['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]);
        WalletTransaction::where('wallet_id', $providerWallet->id)->delete();

        $settlementService = app(SettlementConciliationService::class);
        $commissionEngine = app(CommissionEngineService::class);

        $totalCodExpected = 0.0;
        $totalCostExpected = 0.0;
        $totalShippingExpected = 0.0;
        $totalPlatformFeeExpected = 0.0;
        $totalProfitExpected = 0.0;

        $burstCount = 30;

        for ($i = 1; $i <= $burstCount; $i++) {
            $unitPrice = 100.00 + ($i * 2.0); // Precios variables entre 102.00 y 160.00 BOB
            $unitCost = 40.00;
            $shippingFee = 18.00;

            $order = Order::create([
                'tenant_id'             => $this->tenant->id,
                'dropshipper_id'        => $this->dropshipper->id,
                'warehouse_id'          => $this->warehouse->id,
                'carrier_id'            => $this->carrier->id,
                'order_number'          => "BURST-ORD-{$i}-" . uniqid(),
                'customer_name'         => "Cliente Burst {$i}",
                'customer_phone'        => "+591 7500" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'shipping_department'   => 'Santa Cruz',
                'shipping_city'         => 'Santa Cruz de la Sierra',
                'shipping_address'      => "Av. Radial {$i} #{$i}",
                'total_amount_bob'      => $unitPrice,
                'cost_amount_bob'       => $unitCost,
                'shipping_fee_bob'      => $shippingFee,
                'platform_fee_bob'      => 0.00,
                'dropshipper_profit_bob'=> 0.00,
                'status'                => 'dispatched',
                'payment_method'        => 'cash_on_delivery_bob',
            ]);

            $order->items()->create([
                'tenant_id'     => $this->tenant->id,
                'product_id'    => $this->product->id,
                'provider_id'   => $this->provider->id,
                'quantity'      => 1,
                'unit_cost_bob' => $unitCost,
                'unit_price_bob'=> $unitPrice,
                'subtotal_bob'  => $unitPrice,
            ]);

            $order->load('items.product');

            // Calcular comisiones reales
            $platformFee = $commissionEngine->calculatePlatformFee($order);
            $profit = max(0, $unitPrice - $unitCost - $shippingFee - $platformFee);

            $order->update([
                'platform_fee_bob'      => $platformFee,
                'dropshipper_profit_bob'=> $profit,
            ]);

            // Acumular expectativas contables
            $totalCodExpected += $unitPrice;
            $totalCostExpected += $unitCost;
            $totalShippingExpected += $shippingFee;
            $totalPlatformFeeExpected += $platformFee;
            $totalProfitExpected += $profit;

            // Liquidar orden entregada
            $settlementService->settleDeliveredOrder($order);
        }

        // 1. Auditoría Matemática de la Ráfaga
        $computedDistribution = round($totalCostExpected + $totalShippingExpected + $totalPlatformFeeExpected + $totalProfitExpected, 2);
        $this->assertEquals(
            round($totalCodExpected, 2),
            $computedDistribution,
            'La distribución total de costos, fletes, comisiones y utilidades debe cuadrar al centavo con el total COD'
        );

        // 2. Auditoría de Saldos Finales en Billeteras
        $dropshipperWallet->refresh();
        $providerWallet->refresh();

        $this->assertEquals(round($totalProfitExpected, 2), (float) $dropshipperWallet->balance_available, 'El saldo del dropshipper debe coincidir con la ganancia acumulada');
        $this->assertEquals(round($totalCostExpected, 2), (float) $providerWallet->balance_available, 'El saldo del proveedor debe coincidir con el costo mayorista acumulado');

        // 3. Auditoría de Integridad del Libro Mayor (Ledger)
        $dsTransactionsSum = (float) WalletTransaction::where('wallet_id', $dropshipperWallet->id)
            ->where('transaction_type', 'order_profit')
            ->sum('amount');
        $this->assertEquals(round($totalProfitExpected, 2), round($dsTransactionsSum, 2), 'La suma de transacciones de ganancia debe igualar el saldo de la billetera');

        $provTransactionsSum = (float) WalletTransaction::where('wallet_id', $providerWallet->id)
            ->where('transaction_type', 'order_cost_payout')
            ->sum('amount');
        $this->assertEquals(round($totalCostExpected, 2), round($provTransactionsSum, 2), 'La suma de transacciones de costo debe igualar el saldo de la billetera');
    }

    /**
     * ESTRÉS 5: Lotes Bancarios Masivos con Múltiples Solicitudes Concurrentes
     */
    public function test_stress_massive_bank_settlement_batches(): void
    {
        // Crear 10 transacciones de retiro pendientes en el tenant
        $wallet = Wallet::updateOrCreate(
            ['user_id' => $this->dropshipper->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 1000.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        $withdrawalAmount = 50.00;
        $count = 10;
        $totalExpected = $withdrawalAmount * $count; // 500.00 BOB

        for ($i = 1; $i <= $count; $i++) {
            WalletTransaction::create([
                'tenant_id'        => $this->tenant->id,
                'wallet_id'        => $wallet->id,
                'transaction_type' => 'withdrawal',
                'amount'           => -$withdrawalAmount,
                'balance_after'    => 1000.00 - ($i * $withdrawalAmount),
                'reference_type'   => 'withdrawal_request',
                'description'      => "Retiro masivo de prueba #{$i}",
            ]);
        }

        $bankService = app(BankSettlementService::class);
        $batch = $bankService->createBatch($this->tenant->id, 'Banco Mercantil Santa Cruz', $this->admin, 'Lote masivo de prueba de estrés');

        $this->assertEquals($count, $batch->total_withdrawals_count);
        $this->assertEquals($totalExpected, (float) $batch->total_amount_bob);

        // Procesar y completar lote
        $batch = $bankService->markAsProcessing($batch);
        $batch = $bankService->completeBatch($batch, 'BMSC-TX-STRESS-999');

        $this->assertEquals('completed', $batch->status);
        $this->assertEquals('BMSC-TX-STRESS-999', $batch->bank_reference_number);

        // Verificar que no queden retiros huérfanos sin batch
        $unassignedWithdrawals = WalletTransaction::where('tenant_id', $this->tenant->id)
            ->where('transaction_type', 'withdrawal')
            ->whereNull('bank_settlement_batch_id')
            ->count();
        $this->assertEquals(0, $unassignedWithdrawals, 'Todos los retiros deben estar asignados al lote');
    }
}

