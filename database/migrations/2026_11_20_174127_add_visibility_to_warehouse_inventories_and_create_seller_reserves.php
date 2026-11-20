<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar campos de visibilidad a warehouse_inventories
        Schema::table('warehouse_inventories', function (Blueprint $table) {
            $table->boolean('visible_public')->default(true)->after('stock_available')
                ->comment('Si el stock de este producto en esta bodega es visible para el catálogo público');
            $table->boolean('visible_sellers')->default(true)->after('visible_public')
                ->comment('Si este producto es visible/ofertable a dropshippers/vendedores');
        });

        // 2. Nueva tabla de Reservas de Stock por Vendedor
        Schema::create('warehouse_seller_reserves', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('warehouse_id');
            $table->uuid('product_id');
            $table->uuid('dropshipper_id');   // Usuario con rol dropshipper
            $table->uuid('reserved_by');       // Proveedor que hizo la reserva
            $table->integer('quantity')->unsigned();
            $table->string('notes', 400)->nullable();
            $table->string('status', 20)->default('active'); // active, released, expired
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('dropshipper_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reserved_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['tenant_id', 'status']);
            $table->index(['warehouse_id', 'product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_seller_reserves');

        Schema::table('warehouse_inventories', function (Blueprint $table) {
            $table->dropColumn(['visible_public', 'visible_sellers']);
        });
    }
};
