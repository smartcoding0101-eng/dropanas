<?php

namespace App\Console\Commands;

use App\Models\InventoryKardex;
use App\Models\User;
use App\Models\WarehouseInventory;
use App\Services\Inventory\KardexService;
use Illuminate\Console\Command;

class InitKardexBalancesCommand extends Command
{
    protected $signature = 'kardex:init {--force : Sobrescribe o reinicia saldos de apertura}';
    protected $description = 'Inicializa los saldos de apertura en el Kardex Global para todos los productos e inventarios existentes';

    public function handle(KardexService $kardexService): int
    {
        $this->info("=== Inicializando Saldos de Apertura en Kardex Global ===");

        $admin = User::where('role', 'tenant_admin')->first();
        $adminId = $admin ? $admin->id : null;

        $inventories = WarehouseInventory::with(['product', 'warehouse'])->get();
        $count = 0;

        foreach ($inventories as $inv) {
            $hasOpening = InventoryKardex::withoutGlobalScopes()
                ->where('warehouse_id', $inv->warehouse_id)
                ->where('product_id', $inv->product_id)
                ->where('movement_type', 'opening_balance')
                ->exists();

            if (!$hasOpening || $this->option('force')) {
                $kardexService->recordOpeningBalance(
                    $inv,
                    $adminId,
                    'Saldo inicial de apertura del ecosistema Dropana Bolivia'
                );
                $count++;
            }
        }

        $this->info("Se inicializaron {$count} registros de apertura en el Kardex Global con éxito.");
        return Command::SUCCESS;
    }
}
