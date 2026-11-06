<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reglas de Comisiones Variables
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name', 150);
            $table->integer('priority')->default(100); // Menor valor = mayor prioridad de aplicación
            $table->string('rule_scope', 50); // global, category, provider, dropshipper_tier, carrier
            $table->uuid('scope_reference_id')->nullable();
            $table->string('commission_type', 30); // percentage, fixed_amount, hybrid
            $table->decimal('percentage_rate', 6, 4)->nullable(); // ej: 0.0500 = 5%
            $table->decimal('fixed_fee_bob', 12, 2)->nullable();
            $table->decimal('min_fee_bob', 12, 2)->nullable();
            $table->decimal('max_fee_bob', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_active', 'priority']);
        });

        // 2. Pedidos y Envíos COD
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('dropshipper_id'); // Usuario dropshipper
            $table->uuid('warehouse_id'); // Desde qué bodega se despacha
            $table->uuid('carrier_id')->nullable(); // DEX Bolivia, Cartero, etc.
            $table->uuid('shipping_manifest_id')->nullable();
            $table->string('order_number', 50);
            $table->string('tracking_number', 60)->nullable();
            
            // Datos del Cliente Final en Bolivia
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30); // Formato boliviano
            $table->string('customer_document_id', 30)->nullable(); // CI para emisión o recepción
            $table->string('shipping_department', 50); // Santa Cruz, La Paz, etc.
            $table->string('shipping_city', 100);
            $table->string('shipping_address', 255);
            $table->string('shipping_reference', 255)->nullable();
            $table->decimal('customer_lat', 10, 8)->nullable();
            $table->decimal('customer_lng', 11, 8)->nullable();

            // Modelo Financiero y Desglose en BOB
            $table->decimal('total_amount_bob', 14, 2); // Monto a recaudar al entregar (COD)
            $table->decimal('cost_amount_bob', 14, 2); // Costo mayorista total del producto
            $table->decimal('shipping_fee_bob', 10, 2); // Flete cobrado por el courier
            $table->decimal('platform_fee_bob', 10, 2); // Comisión retenida por la plataforma
            $table->decimal('dropshipper_profit_bob', 14, 2); // Ganancia neta para el dropshipper

            // Estados del Ciclo de Vida
            $table->string('status', 40)->default('pending_confirmation'); 
            // pending_confirmation, confirmed, ready_to_pack, dispatched, in_transit, delivered, settled, cancelled, returned

            $table->string('payment_method', 40)->default('cash_on_delivery_bob'); 
            // cash_on_delivery_bob, qr_interoperable, binance_pay, takenos
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('settled_at')->nullable(); // Cuando los fondos se liberan en la billetera
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('dropshipper_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('carrier_id')->references('id')->on('carriers')->onDelete('set null');
            $table->foreign('shipping_manifest_id')->references('id')->on('shipping_manifests')->onDelete('set null');
            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'status', 'created_at']);
        });

        // 3. Items del Pedido
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('order_id');
            $table->uuid('product_id');
            $table->uuid('provider_id'); // Proveedor dueño del producto
            $table->integer('quantity');
            $table->decimal('unit_cost_bob', 12, 2);
            $table->decimal('unit_price_bob', 12, 2);
            $table->decimal('subtotal_bob', 14, 2);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 4. Billeteras Virtuales en Moneda BOB (y espejo USD)
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id'); // Pertenece a un dropshipper o a un proveedor
            $table->string('currency', 3)->default('BOB');
            $table->decimal('balance_available', 15, 2)->default(0.00); // Disponible para retiro
            $table->decimal('balance_pending', 15, 2)->default(0.00); // Retenido / en camino (pedidos despachados pero no conciliados)
            $table->decimal('balance_withdrawn', 15, 2)->default(0.00); // Histórico retirado
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['tenant_id', 'user_id', 'currency']);
        });

        // 5. Libro Mayor de Transacciones de Billetera (Ledger Auditable)
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('wallet_id');
            $table->string('transaction_type', 40); 
            // order_profit, order_cost_payout, withdrawal_request, withdrawal_paid, penalty_fee, manual_credit
            $table->decimal('amount', 15, 2); // Positivo o negativo
            $table->decimal('balance_after', 15, 2);
            $table->string('reference_type', 50)->nullable(); // order, withdrawal_request
            $table->uuid('reference_id')->nullable();
            $table->string('description', 255);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->index(['tenant_id', 'wallet_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('commission_rules');
    }
};
