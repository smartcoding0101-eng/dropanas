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
        Schema::create('inventory_kardex', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('warehouse_id');
            $table->uuid('product_id');
            $table->uuid('user_id')->nullable();

            // Clasificación del movimiento
            $table->string('movement_category', 30); // inbound, outbound, hold, release, adjustment
            $table->string('movement_type', 50); 
            // Posibles valores de movement_type:
            // - opening_balance (Saldo inicial de apertura)
            // - purchase_receipt (Ingreso por orden de compra)
            // - sale_reservation (Reserva por nuevo pedido)
            // - sale_dispatch (Salida física por pedido entregado/despachado)
            // - order_cancellation (Cancelación de pedido antes de despacho)
            // - order_annulment (Anulación por operador/error)
            // - return_restock (Devolución física apta para reventa)
            // - return_damaged (Devolución con producto dañado / merma)
            // - transfer_out (Salida por transferencia a otra bodega)
            // - transfer_in (Entrada por transferencia desde otra bodega)
            // - audit_adjustment_in (Ajuste positivo por conteo físico)
            // - audit_adjustment_out (Ajuste negativo por merma o rotura)

            $table->string('reference_type', 50)->nullable(); // order, purchase_order, stock_transfer, audit, manual
            $table->string('reference_id', 50)->nullable();
            $table->string('document_number', 100)->nullable(); // Nro Pedido, Guía DEX, PO, TR

            // Valores de cantidad y costeo fijo
            $table->integer('quantity'); // Variación (+ o -)
            $table->decimal('unit_cost_bob', 12, 2)->default(0);
            $table->decimal('total_cost_bob', 14, 2)->default(0);

            // Trazabilidad de saldos anteriores y resultantes
            $table->integer('stock_physical_before')->default(0);
            $table->integer('stock_physical_after')->default(0);
            $table->integer('stock_reserved_before')->default(0);
            $table->integer('stock_reserved_after')->default(0);
            $table->integer('stock_available_before')->default(0);
            $table->integer('stock_available_after')->default(0);

            $table->string('condition', 30)->default('optimal'); // optimal, damaged, defective
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['tenant_id', 'warehouse_id', 'product_id'], 'idx_kardex_warehouse_product');
            $table->index(['tenant_id', 'movement_type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_kardex');
    }
};
