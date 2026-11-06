<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Suite de Invariantes Contables, Integridad Referencial y Sincronización de Registros
 */
class AccountingInvariantsAndSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    /**
     * INVARIANTE 1: El comando audit:reconcile-ledgers debe pasar exitosamente con 0 discrepancias
     */
    public function test_audit_reconcile_ledgers_command_passes_cleanly(): void
    {
        $exitCode = Artisan::call('audit:reconcile-ledgers');
        $this->assertEquals(0, $exitCode, 'El comando de auditoría contable debe retornar exit code 0');

        $output = Artisan::output();
        $this->assertStringContainsString('QA AUDIT PASSED', $output);
        $this->assertStringContainsString('Zero Drift', $output);
    }

    /**
     * INVARIANTE 2: Consistencia del Stock Global = Sumatoria de existencias en todas las Bodegas
     */
    public function test_invariant_product_global_stock_equals_sum_of_warehouse_inventories(): void
    {
        $products = Product::with('warehouseInventories')->get();

        $this->assertNotEmpty($products);

        foreach ($products as $product) {
            $expectedSum = (int) $product->warehouseInventories->sum('stock_available');
            $actualTotal = $product->total_available_stock;

            $this->assertEquals(
                $expectedSum,
                $actualTotal,
                "Inconsistencia en el producto '{$product->name}' (SKU: {$product->sku}): el stock total calculado ({$actualTotal}) no coincide con la suma de bodegas ({$expectedSum})."
            );
        }
    }

    /**
     * INVARIANTE 3: Integridad de Billeteras y Libro Mayor (Zero-Drift Ledger Invariant)
     */
    public function test_invariant_all_wallets_match_exact_transaction_ledger_balance(): void
    {
        $wallets = Wallet::with('transactions')->get();

        foreach ($wallets as $wallet) {
            $sumTransactions = (float) $wallet->transactions->sum('amount');
            $balanceAvailable = (float) $wallet->balance_available;

            $diff = round(abs($sumTransactions - $balanceAvailable), 2);

            $this->assertEquals(
                0.00,
                $diff,
                "Descuadre financiero en billetera {$wallet->id}: saldo disponible ({$balanceAvailable}) difiere de la suma del ledger ({$sumTransactions}) por Bs {$diff}."
            );
        }
    }

    /**
     * INVARIANTE 4: No existen transacciones huérfanas en el sistema
     */
    public function test_invariant_no_orphan_wallet_transactions_exist(): void
    {
        $orphanTransactions = WalletTransaction::whereDoesntHave('wallet')->count();
        $this->assertEquals(0, $orphanTransactions, 'No debe existir ninguna transacción de billetera huérfana en la base de datos');

        $orphanItems = \App\Models\OrderItem::whereDoesntHave('order')->count();
        $this->assertEquals(0, $orphanItems, 'No debe existir ningún ítem de pedido huérfano');
    }
}

