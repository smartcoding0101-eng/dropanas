<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\CarrierCoverage;
use App\Models\Category;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\Currency\BcbExchangeRateService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BoliviaEcosystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(BcbExchangeRateService $exchangeService): void
    {
        // 1. Sincronizar Cotización Inicial BCB
        $exchangeService->syncRate();

        // 2. Tenant Principal para Bolivia (Evitar duplicado)
        $tenant = Tenant::firstOrCreate(
            ['domain' => 'dropana.bo'],
            [
                'code' => 'dropana_bolivia',
                'company_name' => 'Dropana Bolivia S.R.L.',
                'legal_nit' => '1028493021',
                'country_code' => 'BO',
                'currency_code' => 'BOB',
                'support_email' => 'soporte@dropana.bo',
                'support_phone' => '+591 70012345',
                'is_active' => true,
            ]
        );

        // 3. Usuarios Clave de Demostración
        $admin = User::firstOrCreate(
            ['email' => 'admin@dropana.bo'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Administrador Dropana Bolivia',
                'password' => Hash::make('password123'),
                'role' => 'tenant_admin',
                'phone' => '+591 70011111',
                'document_id' => '5432101 SC',
                'city' => 'Santa Cruz de la Sierra',
                'department' => 'Santa Cruz',
            ]
        );

        $provider = User::firstOrCreate(
            ['email' => 'proveedor@dropana.bo'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Importadora Oriental Mayorista',
                'password' => Hash::make('password123'),
                'role' => 'provider',
                'phone' => '+591 70022222',
                'document_id' => '1029384019',
                'company_or_store_name' => 'Megabodega Bolivia S.A.',
                'city' => 'Santa Cruz de la Sierra',
                'department' => 'Santa Cruz',
            ]
        );

        $dropshipper = User::firstOrCreate(
            ['email' => 'dropshipper@dropana.bo'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Vendedor E-commerce Emprendedor',
                'password' => Hash::make('password123'),
                'role' => 'dropshipper',
                'phone' => '+591 70033333',
                'document_id' => '7891234 LP',
                'company_or_store_name' => 'Tienda Digital Flash',
                'city' => 'La Paz',
                'department' => 'La Paz',
            ]
        );

        $carrierUser = User::firstOrCreate(
            ['email' => 'carrier@dropana.bo'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Repartidor DEX Express Carlos',
                'password' => Hash::make('password123'),
                'role' => 'carrier',
                'phone' => '+591 70044444',
                'document_id' => '6543210 SC',
                'company_or_store_name' => 'DEX Courier SCZ Móvil #14',
                'city' => 'Santa Cruz de la Sierra',
                'department' => 'Santa Cruz',
            ]
        );

        $fulfillmentUser = User::firstOrCreate(
            ['email' => 'fulfillment@dropana.bo'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Tienda Online Glow Bolivia',
                'password' => Hash::make('password123'),
                'role' => 'fulfillment_client',
                'phone' => '+591 70055555',
                'document_id' => '8901234 CB',
                'company_or_store_name' => 'Glow Cosmetics Bolivia',
                'city' => 'Cochabamba',
                'department' => 'Cochabamba',
            ]
        );

        // Crear Billeteras Iniciales para Dropshipper
        $dropshipperWallet = Wallet::firstOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $dropshipper->id],
            [
                'currency' => 'BOB',
                'balance_available' => 1845.50,
                'balance_pending' => 450.00,
                'balance_withdrawn' => 3200.00,
            ]
        );

        // 4. Bodegas Principales en Bolivia
        $warehouseSCZ = Warehouse::firstOrCreate(
            ['code' => 'WH-SCZ-01'],
            [
                'tenant_id' => $tenant->id,
                'provider_id' => $provider->id,
                'name' => 'Megabodega Central Parque Industrial',
                'department' => 'Santa Cruz',
                'city' => 'Santa Cruz de la Sierra',
                'address' => 'Parque Industrial PI-27, Manzana 12',
                'contact_phone' => '+591 3 3456789',
                'is_active' => true,
            ]
        );

        $warehouseLPZ = Warehouse::firstOrCreate(
            ['code' => 'WH-LPZ-01'],
            [
                'tenant_id' => $tenant->id,
                'provider_id' => $provider->id,
                'name' => 'Bodega Eje Troncal El Alto',
                'department' => 'La Paz',
                'city' => 'El Alto',
                'address' => 'Av. 6 de Marzo, Zona Senkata',
                'contact_phone' => '+591 2 2891234',
                'is_active' => true,
            ]
        );

        $warehouseCBB = Warehouse::firstOrCreate(
            ['code' => 'WH-CBB-01'],
            [
                'tenant_id' => $tenant->id,
                'provider_id' => $provider->id,
                'name' => 'Bodega Valle Central Cochabamba',
                'department' => 'Cochabamba',
                'city' => 'Cochabamba',
                'address' => 'Av. Blanco Galindo Km 4.5',
                'contact_phone' => '+591 4 4567890',
                'is_active' => true,
            ]
        );

        // 5. Categorías
        $catElectro = Category::firstOrCreate(['slug' => 'tecnologia-y-gadgets'], [
            'tenant_id' => $tenant->id,
            'name' => 'Tecnología y Gadgets',
            'description' => 'Dispositivos electrónicos, accesorios para celulares y hogar',
        ]);

        $catBelleza = Category::firstOrCreate(['slug' => 'salud-y-belleza'], [
            'tenant_id' => $tenant->id,
            'name' => 'Salud y Belleza',
            'description' => 'Cosméticos, cuidado de la piel y depilación',
        ]);

        $catHogar = Category::firstOrCreate(['slug' => 'hogar-y-cocina'], [
            'tenant_id' => $tenant->id,
            'name' => 'Hogar y Cocina',
            'description' => 'Artículos prácticos de alta rotación para el hogar',
        ]);

        $catModa = Category::firstOrCreate(['slug' => 'moda-y-accesorios'], [
            'tenant_id' => $tenant->id,
            'name' => 'Moda y Accesorios',
            'description' => 'Relojes inteligentes, carteras y complementos',
        ]);

        // 6. Transportadoras en Bolivia
        $dex = Carrier::firstOrCreate(['code' => 'dex_bolivia'], [
            'tenant_id' => $tenant->id,
            'name' => 'DEX Bolivia Express',
            'contact_name' => 'Operaciones DEX',
            'contact_phone' => '+591 71000001',
            'has_cod_service' => true,
            'integration_mode' => 'manifest_file',
        ]);

        $cartero = Carrier::firstOrCreate(['code' => 'cartero_courier'], [
            'tenant_id' => $tenant->id,
            'name' => 'Cartero Courier',
            'contact_name' => 'Atención Corporativa Cartero',
            'contact_phone' => '+591 71000002',
            'has_cod_service' => true,
            'integration_mode' => 'manifest_file',
        ]);

        // Coberturas y Tarifas Eje Troncal
        CarrierCoverage::firstOrCreate(
            ['carrier_id' => $dex->id, 'department' => 'Santa Cruz'],
            [
                'tenant_id' => $tenant->id,
                'city' => 'Santa Cruz de la Sierra',
                'zone_type' => 'urban',
                'base_shipping_bob' => 18.00,
                'cod_fee_percentage' => 0.00,
                'estimated_delivery_days' => 1,
            ]
        );

        CarrierCoverage::firstOrCreate(
            ['carrier_id' => $dex->id, 'department' => 'La Paz'],
            [
                'tenant_id' => $tenant->id,
                'city' => 'La Paz',
                'zone_type' => 'urban',
                'base_shipping_bob' => 25.00,
                'cod_fee_percentage' => 0.00,
                'estimated_delivery_days' => 2,
            ]
        );

        CarrierCoverage::firstOrCreate(
            ['carrier_id' => $cartero->id, 'department' => 'Cochabamba'],
            [
                'tenant_id' => $tenant->id,
                'city' => 'Cochabamba',
                'zone_type' => 'urban',
                'base_shipping_bob' => 22.00,
                'cod_fee_percentage' => 0.00,
                'estimated_delivery_days' => 2,
            ]
        );

        // 7. Catálogo Completo de Productos Ganadores
        $productsData = [
            [
                'name' => 'Audífonos Bluetooth Inalámbricos F9 Pro',
                'slug' => 'audifonos-bluetooth-f9-pro',
                'sku' => 'AUD-F9-PRO-BO',
                'category_id' => $catElectro->id,
                'description' => 'Audífonos con cancelación de ruido pasiva y pantalla LED digital con batería externa.',
                'cost_price_bob' => 38.00,
                'suggested_price_bob' => 99.00,
                'min_price_bob' => 85.00,
                'weight_kg' => 0.150,
                'stock_scz' => 350,
                'stock_lpz' => 120,
            ],
            [
                'name' => 'Smartwatch Ultra 8 Serie Titanium con Correa Naranja',
                'slug' => 'smartwatch-ultra-8-titanium',
                'sku' => 'SW-ULTRA8-TIT',
                'category_id' => $catElectro->id,
                'description' => 'Reloj inteligente con llamadas Bluetooth, monitor de ritmo cardíaco, podómetro y carga magnética.',
                'cost_price_bob' => 85.00,
                'suggested_price_bob' => 199.00,
                'min_price_bob' => 170.00,
                'weight_kg' => 0.220,
                'stock_scz' => 210,
                'stock_lpz' => 90,
            ],
            [
                'name' => 'Depiladora Láser Portátil IPL Luz Pulsada Definitiva',
                'slug' => 'depiladora-laser-ipl-portatil',
                'sku' => 'DEP-IPL-990K',
                'category_id' => $catBelleza->id,
                'description' => 'Sistema de reducción permanente del vello corporal con 990.000 pulsaciones y 5 niveles de intensidad.',
                'cost_price_bob' => 95.00,
                'suggested_price_bob' => 240.00,
                'min_price_bob' => 210.00,
                'weight_kg' => 0.380,
                'stock_scz' => 140,
                'stock_lpz' => 60,
            ],
            [
                'name' => 'Cepillo Secador Voluminizador 3 en 1 Cerámica Iónica',
                'slug' => 'cepillo-secador-voluminizador-3en1',
                'sku' => 'CEP-SEC-3IN1',
                'category_id' => $catBelleza->id,
                'description' => 'Alisa, seca y da volumen en un solo paso gracias a su flujo de aire 360 y revestimiento de titanio.',
                'cost_price_bob' => 45.00,
                'suggested_price_bob' => 120.00,
                'min_price_bob' => 99.00,
                'weight_kg' => 0.450,
                'stock_scz' => 300,
                'stock_lpz' => 150,
            ],
            [
                'name' => 'Mini Licuadora Portátil USB Recargable 6 Cuchillas',
                'slug' => 'mini-licuadora-portatil-usb-6-cuchillas',
                'sku' => 'LIC-MINI-USB6',
                'category_id' => $catHogar->id,
                'description' => 'Prepara batidos y jugos frescos al instante en el gimnasio o la oficina. Batería recargable USB de 2000mAh.',
                'cost_price_bob' => 35.00,
                'suggested_price_bob' => 89.00,
                'min_price_bob' => 75.00,
                'weight_kg' => 0.310,
                'stock_scz' => 280,
                'stock_lpz' => 110,
            ],
            [
                'name' => 'Foco Cámara de Seguridad 360° WiFi Panorámica HD',
                'slug' => 'foco-camara-seguridad-360-wifi',
                'sku' => 'CAM-FOCO-360',
                'category_id' => $catElectro->id,
                'description' => 'Cámara de vigilancia oculta en bombilla estándar E27 con visión nocturna, audio bidireccional y sensor de movimiento.',
                'cost_price_bob' => 60.00,
                'suggested_price_bob' => 150.00,
                'min_price_bob' => 130.00,
                'weight_kg' => 0.250,
                'stock_scz' => 190,
                'stock_lpz' => 80,
            ],
        ];

        $createdProducts = [];
        foreach ($productsData as $data) {
            $prod = Product::firstOrCreate(
                ['sku' => $data['sku']],
                [
                    'tenant_id' => $tenant->id,
                    'provider_id' => $provider->id,
                    'category_id' => $data['category_id'],
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'description' => $data['description'],
                    'cost_price_bob' => $data['cost_price_bob'],
                    'suggested_price_bob' => $data['suggested_price_bob'],
                    'min_price_bob' => $data['min_price_bob'],
                    'weight_kg' => $data['weight_kg'],
                    'status' => 'published',
                    'is_cod_available' => true,
                ]
            );

            WarehouseInventory::firstOrCreate(
                ['warehouse_id' => $warehouseSCZ->id, 'product_id' => $prod->id],
                [
                    'tenant_id' => $tenant->id,
                    'stock_physical' => $data['stock_scz'],
                    'stock_reserved' => 0,
                    'stock_available' => $data['stock_scz'],
                ]
            );

            WarehouseInventory::firstOrCreate(
                ['warehouse_id' => $warehouseLPZ->id, 'product_id' => $prod->id],
                [
                    'tenant_id' => $tenant->id,
                    'stock_physical' => $data['stock_lpz'],
                    'stock_reserved' => 0,
                    'stock_available' => $data['stock_lpz'],
                ]
            );

            $createdProducts[] = $prod;
        }

        // 8. Pedidos COD Históricos para Dashboards y Tracking en Vivo
        $ordersData = [
            [
                'order_number' => 'ORD-2026-001234',
                'tracking_number' => 'DRP-2026-001234',
                'customer_name' => 'Rodrigo Morales Vaca',
                'customer_phone' => '+591 78512345',
                'customer_document_id' => '8342190 SC',
                'shipping_department' => 'Santa Cruz',
                'shipping_city' => 'Santa Cruz de la Sierra',
                'shipping_address' => 'Av. Beni entre 3er y 4to Anillo #342',
                'shipping_reference' => 'Casa portón café frente a farmacia',
                'status' => 'in_transit',
                'payment_method' => 'cash_on_delivery_bob',
                'total_amount_bob' => 240.00,
                'cost_amount_bob' => 95.00,
                'shipping_fee_bob' => 18.00,
                'platform_fee_bob' => 10.00,
                'dropshipper_profit_bob' => 117.00,
                'delivery_attempts' => 1,
                'created_at' => Carbon::now()->subHours(6),
                'dispatched_at' => Carbon::now()->subHours(4),
                'product_index' => 2, // Depiladora
            ],
            [
                'order_number' => 'ORD-2026-001235',
                'tracking_number' => 'DRP-2026-001235',
                'customer_name' => 'María Elena Gutiérrez',
                'customer_phone' => '+591 76298765',
                'customer_document_id' => '4928172 LP',
                'shipping_department' => 'La Paz',
                'shipping_city' => 'La Paz',
                'shipping_address' => 'Calle 21 de Calacoto #890, Edif. Los Pinos Piso 4',
                'shipping_reference' => 'Dejar en recepción con portería',
                'status' => 'delivered',
                'payment_method' => 'qr_interoperable',
                'total_amount_bob' => 199.00,
                'cost_amount_bob' => 85.00,
                'shipping_fee_bob' => 25.00,
                'platform_fee_bob' => 8.00,
                'dropshipper_profit_bob' => 81.00,
                'delivery_attempts' => 1,
                'created_at' => Carbon::now()->subDays(2),
                'dispatched_at' => Carbon::now()->subDays(1),
                'delivered_at' => Carbon::now()->subHours(18),
                'settled_at' => Carbon::now()->subHours(12),
                'product_index' => 1, // Smartwatch
            ],
            [
                'order_number' => 'ORD-2026-001236',
                'tracking_number' => 'DRP-2026-001236',
                'customer_name' => 'Carlos Daniel Paredes',
                'customer_phone' => '+591 71345678',
                'customer_document_id' => '6547890 CB',
                'shipping_department' => 'Cochabamba',
                'shipping_city' => 'Cochabamba',
                'shipping_address' => 'Av. América Oeste #1120',
                'shipping_reference' => 'Condominio El Rosal Casa 14',
                'status' => 'pending_confirmation',
                'payment_method' => 'cash_on_delivery_bob',
                'total_amount_bob' => 150.00,
                'cost_amount_bob' => 60.00,
                'shipping_fee_bob' => 22.00,
                'platform_fee_bob' => 6.00,
                'dropshipper_profit_bob' => 62.00,
                'delivery_attempts' => 0,
                'created_at' => Carbon::now()->subMinutes(45),
                'product_index' => 5, // Foco Cámara
            ],
            [
                'order_number' => 'ORD-2026-001237',
                'tracking_number' => 'DRP-2026-001237',
                'customer_name' => 'Claudia Patricia Suárez',
                'customer_phone' => '+591 77011223',
                'customer_document_id' => '7845123 SC',
                'shipping_department' => 'Santa Cruz',
                'shipping_city' => 'Santa Cruz de la Sierra',
                'shipping_address' => 'Barrio Sirari, Calle Los Claveles #45',
                'shipping_reference' => 'Portón negro timbre blanco',
                'status' => 'in_transit',
                'payment_method' => 'cash_on_delivery_bob',
                'total_amount_bob' => 120.00,
                'cost_amount_bob' => 45.00,
                'shipping_fee_bob' => 18.00,
                'platform_fee_bob' => 5.00,
                'dropshipper_profit_bob' => 52.00,
                'delivery_attempts' => 1,
                'novedad_reason' => 'cliente_ausente',
                'novedad_notes' => 'El cliente no se encontraba, se coordinó reintento para la tarde.',
                'rescheduled_delivery_at' => Carbon::now()->addHours(3),
                'created_at' => Carbon::now()->subHours(10),
                'dispatched_at' => Carbon::now()->subHours(5),
                'product_index' => 3, // Cepillo Secador
            ],
            [
                'order_number' => 'ORD-2026-001238',
                'tracking_number' => 'DRP-2026-001238',
                'customer_name' => 'Jorge Luis Zambrana',
                'customer_phone' => '+591 72155443',
                'customer_document_id' => '9012431 SC',
                'shipping_department' => 'Santa Cruz',
                'shipping_city' => 'Santa Cruz de la Sierra',
                'shipping_address' => 'Av. San Martín, Equipetrol Norte #650',
                'shipping_reference' => 'Torre Duo, Oficina 302',
                'status' => 'delivered',
                'payment_method' => 'cash_on_delivery_bob',
                'total_amount_bob' => 99.00,
                'cost_amount_bob' => 38.00,
                'shipping_fee_bob' => 18.00,
                'platform_fee_bob' => 4.00,
                'dropshipper_profit_bob' => 39.00,
                'delivery_attempts' => 1,
                'created_at' => Carbon::now()->subDays(3),
                'dispatched_at' => Carbon::now()->subDays(2),
                'delivered_at' => Carbon::now()->subDays(1),
                'settled_at' => Carbon::now()->subDays(1),
                'product_index' => 0, // F9 Pro
            ],
        ];

        foreach ($ordersData as $oData) {
            $selectedProduct = $createdProducts[$oData['product_index']];

            $order = Order::firstOrCreate(
                ['order_number' => $oData['order_number']],
                [
                    'tenant_id' => $tenant->id,
                    'dropshipper_id' => $dropshipper->id,
                    'warehouse_id' => $warehouseSCZ->id,
                    'carrier_id' => $dex->id,
                    'tracking_number' => $oData['tracking_number'],
                    'customer_name' => $oData['customer_name'],
                    'customer_phone' => $oData['customer_phone'],
                    'customer_document_id' => $oData['customer_document_id'],
                    'shipping_department' => $oData['shipping_department'],
                    'shipping_city' => $oData['shipping_city'],
                    'shipping_address' => $oData['shipping_address'],
                    'shipping_reference' => $oData['shipping_reference'],
                    'status' => $oData['status'],
                    'payment_method' => $oData['payment_method'],
                    'total_amount_bob' => $oData['total_amount_bob'],
                    'cost_amount_bob' => $oData['cost_amount_bob'],
                    'shipping_fee_bob' => $oData['shipping_fee_bob'],
                    'platform_fee_bob' => $oData['platform_fee_bob'],
                    'dropshipper_profit_bob' => $oData['dropshipper_profit_bob'],
                    'delivery_attempts' => $oData['delivery_attempts'],
                    'novedad_reason' => $oData['novedad_reason'] ?? null,
                    'novedad_notes' => $oData['novedad_notes'] ?? null,
                    'rescheduled_delivery_at' => $oData['rescheduled_delivery_at'] ?? null,
                    'dispatched_at' => $oData['dispatched_at'] ?? null,
                    'delivered_at' => $oData['delivered_at'] ?? null,
                    'settled_at' => $oData['settled_at'] ?? null,
                    'created_at' => $oData['created_at'],
                ]
            );

            OrderItem::firstOrCreate(
                ['order_id' => $order->id, 'product_id' => $selectedProduct->id],
                [
                    'tenant_id' => $tenant->id,
                    'provider_id' => $provider->id,
                    'quantity' => 1,
                    'unit_cost_bob' => $oData['cost_amount_bob'],
                    'unit_price_bob' => $oData['total_amount_bob'],
                    'subtotal_bob' => $oData['total_amount_bob'],
                ]
            );
        }

        // 9. Transacciones de Billetera Históricas
        WalletTransaction::firstOrCreate(
            ['wallet_id' => $dropshipperWallet->id, 'reference_id' => 'ORD-2026-001235'],
            [
                'tenant_id' => $tenant->id,
                'transaction_type' => 'credit_order_profit',
                'amount' => 81.00,
                'balance_after' => 1845.50,
                'reference_type' => 'order',
                'description' => 'Ganancia acreditada por entrega exitosa ORD-2026-001235',
            ]
        );

        WalletTransaction::firstOrCreate(
            ['wallet_id' => $dropshipperWallet->id, 'reference_id' => 'ORD-2026-001238'],
            [
                'tenant_id' => $tenant->id,
                'transaction_type' => 'credit_order_profit',
                'amount' => 39.00,
                'balance_after' => 1764.50,
                'reference_type' => 'order',
                'description' => 'Ganancia acreditada por entrega exitosa ORD-2026-001238',
            ]
        );

        // Registro de saldo inicial y retiro histórico para cuadre contable
        $historicalBatch = \App\Models\BankSettlementBatch::firstOrCreate(
            ['batch_number' => 'BATCH-HIST-INITIAL-01'],
            [
                'tenant_id' => $tenant->id,
                'processed_by_id' => $admin->id,
                'bank_name' => 'Banco Nacional de Bolivia (BNB)',
                'total_withdrawals_count' => 1,
                'total_amount_bob' => 3200.00,
                'status' => \App\Models\BankSettlementBatch::STATUS_COMPLETED,
                'bank_reference_number' => 'REF-HIST-BNB-001',
                'completed_at' => now()->subMonth(),
            ]
        );

        WalletTransaction::firstOrCreate(
            ['wallet_id' => $dropshipperWallet->id, 'reference_id' => 'INIT-BALANCE-01'],
            [
                'tenant_id' => $tenant->id,
                'transaction_type' => 'manual_adjustment',
                'amount' => 4925.50,
                'balance_after' => 4925.50,
                'reference_type' => 'opening_balance',
                'description' => 'Saldo inicial por histórico acumulado de ventas entregadas',
            ]
        );

        WalletTransaction::firstOrCreate(
            ['wallet_id' => $dropshipperWallet->id, 'reference_id' => 'WITHDRAW-HIST-01'],
            [
                'tenant_id' => $tenant->id,
                'transaction_type' => 'withdrawal',
                'amount' => -3200.00,
                'balance_after' => 1725.50,
                'reference_type' => 'withdrawal',
                'bank_settlement_batch_id' => $historicalBatch->id,
                'description' => 'Histórico acumulado de transferencias bancarias completadas',
            ]
        );

        // 10. Reglas de Comisiones
        CommissionRule::firstOrCreate(
            ['name' => 'Comisión Global Plataforma'],
            [
                'tenant_id' => $tenant->id,
                'priority' => 100,
                'rule_scope' => 'global',
                'commission_type' => 'hybrid',
                'percentage_rate' => 0.0500, // 5%
                'fixed_fee_bob' => 2.00,
                'min_fee_bob' => 3.50,
                'max_fee_bob' => 50.00,
                'is_active' => true,
            ]
        );

        CommissionRule::firstOrCreate(
            ['name' => 'Tarifa Reducida Tecnología'],
            [
                'tenant_id' => $tenant->id,
                'priority' => 50,
                'rule_scope' => 'category',
                'scope_reference_id' => $catElectro->id,
                'commission_type' => 'percentage',
                'percentage_rate' => 0.0400, // 4%
                'min_fee_bob' => 2.50,
                'is_active' => true,
            ]
        );

        // Inicializar saldos de apertura en el Kardex Global
        \Illuminate\Support\Facades\Artisan::call('kardex:init');
    }
}
