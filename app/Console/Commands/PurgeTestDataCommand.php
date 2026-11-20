<?php

namespace App\Console\Commands;

use App\Models\BankSettlementBatch;
use App\Models\Carrier;
use App\Models\CarrierCoverage;
use App\Models\Category;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\InventoryAlert;
use App\Models\InventoryKardex;
use App\Models\Lead;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ShippingManifest;
use App\Models\StockTransfer;
use App\Models\StoreIntegration;
use App\Models\StoreWebhookLog;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PurgeTestDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:purge-test-data {--force : Ejecutar limpieza sin confirmación interactiva}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia todos los registros de prueba (productos, pedidos, inventarios, kardex, transacciones) y conserva únicamente los Usuarios, Billeteras en 0 y Configuración Base para carga manual.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando limpieza de registros de prueba en Bolidrop Bolivia...');

        DB::transaction(function () {
            // 1. Limpiar tablas transaccionales, pedidos, inventarios y productos de prueba
            StoreWebhookLog::withoutGlobalScopes()->delete();
            StoreIntegration::withoutGlobalScopes()->delete();
            NotificationLog::withoutGlobalScopes()->delete();
            PurchaseOrderItem::withoutGlobalScopes()->delete();
            PurchaseOrder::withoutGlobalScopes()->delete();
            OrderItem::withoutGlobalScopes()->delete();
            Order::withoutGlobalScopes()->delete();
            ShippingManifest::withoutGlobalScopes()->delete();
            Customer::withoutGlobalScopes()->delete();
            Lead::withoutGlobalScopes()->delete();

            // 2. Limpiar movimientos de Kardex, alertas, transferencias e inventarios de prueba
            InventoryKardex::withoutGlobalScopes()->delete();
            InventoryAlert::withoutGlobalScopes()->delete();
            StockTransfer::withoutGlobalScopes()->delete();
            WarehouseInventory::withoutGlobalScopes()->delete();
            \App\Models\Product::withoutGlobalScopes()->delete();

            // 3. Limpiar transacciones de billetera y lotes bancarios de prueba, y reiniciar saldos de billeteras a 0.00
            WalletTransaction::withoutGlobalScopes()->delete();
            BankSettlementBatch::withoutGlobalScopes()->delete();

            // 4. Asegurar que exista el Tenant Principal y los 5 Usuarios Base activos
            $tenant = Tenant::firstOrCreate(
                ['domain' => 'bolidrop.bo'],
                [
                    'code'          => 'bolidrop_bolivia',
                    'company_name'  => 'Bolidrop Bolivia S.R.L.',
                    'legal_nit'     => '1028493021',
                    'country_code'  => 'BO',
                    'currency_code' => 'BOB',
                    'support_email' => 'soporte@bolidrop.bo',
                    'support_phone' => '+591 70012345',
                    'is_active'     => true,
                ]
            );

            $admin = User::updateOrCreate(
                ['email' => 'admin@bolidrop.bo'],
                [
                    'tenant_id'   => $tenant->id,
                    'name'        => 'Administrador Bolidrop Bolivia',
                    'password'    => Hash::make('password123'),
                    'role'        => 'tenant_admin',
                    'phone'       => '+591 70011111',
                    'document_id' => '5432101 SC',
                    'city'        => 'Santa Cruz de la Sierra',
                    'department'  => 'Santa Cruz',
                    'is_active'   => true,
                ]
            );

            $provider = User::updateOrCreate(
                ['email' => 'proveedor@bolidrop.bo'],
                [
                    'tenant_id'             => $tenant->id,
                    'name'                  => 'Importadora Oriental Mayorista',
                    'password'              => Hash::make('password123'),
                    'role'                  => 'provider',
                    'phone'                 => '+591 70022222',
                    'document_id'           => '1029384019',
                    'company_or_store_name' => 'Megabodega Bolivia S.A.',
                    'city'                  => 'Santa Cruz de la Sierra',
                    'department'            => 'Santa Cruz',
                    'is_active'             => true,
                ]
            );

            $dropshipper = User::updateOrCreate(
                ['email' => 'dropshipper@bolidrop.bo'],
                [
                    'tenant_id'             => $tenant->id,
                    'name'                  => 'Vendedor E-commerce Emprendedor',
                    'password'              => Hash::make('password123'),
                    'role'                  => 'dropshipper',
                    'phone'                 => '+591 70033333',
                    'document_id'           => '7891234 LP',
                    'company_or_store_name' => 'Tienda Digital Flash',
                    'city'                  => 'La Paz',
                    'department'            => 'La Paz',
                    'is_active'             => true,
                ]
            );

            User::updateOrCreate(
                ['email' => 'carrier@bolidrop.bo'],
                [
                    'tenant_id'             => $tenant->id,
                    'name'                  => 'Repartidor DEX Express Carlos',
                    'password'              => Hash::make('password123'),
                    'role'                  => 'carrier',
                    'phone'                 => '+591 70044444',
                    'document_id'           => '6543210 SC',
                    'company_or_store_name' => 'DEX Courier SCZ Móvil #14',
                    'city'                  => 'Santa Cruz de la Sierra',
                    'department'            => 'Santa Cruz',
                    'is_active'             => true,
                ]
            );

            User::updateOrCreate(
                ['email' => 'fulfillment@bolidrop.bo'],
                [
                    'tenant_id'             => $tenant->id,
                    'name'                  => 'Tienda Online Glow Bolivia',
                    'password'              => Hash::make('password123'),
                    'role'                  => 'fulfillment_client',
                    'phone'                 => '+591 70055555',
                    'document_id'           => '8901234 CB',
                    'company_or_store_name' => 'Glow Cosmetics Bolivia',
                    'city'                  => 'Cochabamba',
                    'department'            => 'Cochabamba',
                    'is_active'             => true,
                ]
            );

            // Reiniciar todas las billeteras a Bs 0.00 y asegurar que proveedor y dropshipper tengan su billetera lista
            Wallet::withoutGlobalScopes()->update([
                'balance_available' => 0.00,
                'balance_pending'   => 0.00,
                'balance_withdrawn' => 0.00,
            ]);

            foreach ([$dropshipper, $provider] as $wUser) {
                Wallet::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'user_id' => $wUser->id],
                    [
                        'currency'          => 'BOB',
                        'balance_available' => 0.00,
                        'balance_pending'   => 0.00,
                        'balance_withdrawn' => 0.00,
                    ]
                );
            }

            // 5. Asegurar las 3 Bodegas físicas vacías listas para recibir productos manuales
            Warehouse::updateOrCreate(
                ['code' => 'WH-SCZ-01'],
                [
                    'tenant_id'     => $tenant->id,
                    'provider_id'   => $provider->id,
                    'name'          => 'Megabodega Central Parque Industrial',
                    'department'    => 'Santa Cruz',
                    'city'          => 'Santa Cruz de la Sierra',
                    'address'       => 'Parque Industrial PI-27, Manzana 12',
                    'contact_phone' => '+591 3 3456789',
                    'is_active'     => true,
                ]
            );

            Warehouse::updateOrCreate(
                ['code' => 'WH-LPZ-01'],
                [
                    'tenant_id'     => $tenant->id,
                    'provider_id'   => $provider->id,
                    'name'          => 'Bodega Eje Troncal El Alto',
                    'department'    => 'La Paz',
                    'city'          => 'El Alto',
                    'address'       => 'Av. 6 de Marzo, Zona Senkata',
                    'contact_phone' => '+591 2 2891234',
                    'is_active'     => true,
                ]
            );

            Warehouse::updateOrCreate(
                ['code' => 'WH-CBB-01'],
                [
                    'tenant_id'     => $tenant->id,
                    'provider_id'   => $provider->id,
                    'name'          => 'Bodega Valle Central Cochabamba',
                    'department'    => 'Cochabamba',
                    'city'          => 'Cochabamba',
                    'address'       => 'Av. Blanco Galindo Km 4.5',
                    'contact_phone' => '+591 4 4567890',
                    'is_active'     => true,
                ]
            );

            // 6. Asegurar Transportadoras y Coberturas base para cuando se creen pedidos manuales
            $dex = Carrier::updateOrCreate(['code' => 'dex_bolivia'], [
                'tenant_id'        => $tenant->id,
                'name'             => 'DEX Bolivia Express',
                'contact_name'     => 'Operaciones DEX',
                'contact_phone'    => '+591 71000001',
                'has_cod_service'  => true,
                'integration_mode' => 'manifest_file',
            ]);

            $cartero = Carrier::updateOrCreate(['code' => 'cartero_courier'], [
                'tenant_id'        => $tenant->id,
                'name'             => 'Cartero Courier',
                'contact_name'     => 'Atención Corporativa Cartero',
                'contact_phone'    => '+591 71000002',
                'has_cod_service'  => true,
                'integration_mode' => 'manifest_file',
            ]);

            CarrierCoverage::updateOrCreate(
                ['carrier_id' => $dex->id, 'department' => 'Santa Cruz'],
                [
                    'tenant_id'               => $tenant->id,
                    'city'                    => 'Santa Cruz de la Sierra',
                    'zone_type'               => 'urban',
                    'base_shipping_bob'       => 18.00,
                    'cod_fee_percentage'      => 0.00,
                    'estimated_delivery_days' => 1,
                ]
            );

            CarrierCoverage::updateOrCreate(
                ['carrier_id' => $dex->id, 'department' => 'La Paz'],
                [
                    'tenant_id'               => $tenant->id,
                    'city'                    => 'La Paz',
                    'zone_type'               => 'urban',
                    'base_shipping_bob'       => 25.00,
                    'cod_fee_percentage'      => 0.00,
                    'estimated_delivery_days' => 2,
                ]
            );

            CarrierCoverage::updateOrCreate(
                ['carrier_id' => $cartero->id, 'department' => 'Cochabamba'],
                [
                    'tenant_id'               => $tenant->id,
                    'city'                    => 'Cochabamba',
                    'zone_type'               => 'urban',
                    'base_shipping_bob'       => 22.00,
                    'cod_fee_percentage'      => 0.00,
                    'estimated_delivery_days' => 2,
                ]
            );

            // 7. Regla de Comisión Global
            CommissionRule::updateOrCreate(
                ['name' => 'Comisión Global Plataforma'],
                [
                    'tenant_id'       => $tenant->id,
                    'priority'        => 100,
                    'rule_scope'      => 'global',
                    'commission_type' => 'hybrid',
                    'percentage_rate' => 0.0500,
                    'fixed_fee_bob'   => 2.00,
                    'min_fee_bob'     => 3.50,
                    'max_fee_bob'     => 50.00,
                    'is_active'       => true,
                ]
            );
        });

        $this->info('========================================================================');
        $this->info('LIMPIEZA COMPLETADA CON ÉXITO:');
        $this->line(' • Usuarios conservados: ' . User::count() . ' usuarios activos');
        $this->line(' • Productos de prueba eliminados: ' . Product::count() . ' productos (listo para carga manual)');
        $this->line(' • Pedidos de prueba eliminados: ' . Order::count() . ' pedidos');
        $this->line(' • Inventarios / Kardex reiniciados: ' . WarehouseInventory::count() . ' registros');
        $this->line(' • Billeteras en Bs 0.00 y 3 Bodegas listas para carga manual.');
        $this->info('========================================================================');

        return self::SUCCESS;
    }
}
