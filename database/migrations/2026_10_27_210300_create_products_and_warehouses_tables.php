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
        // 1. Categorías
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('set null');
            $table->unique(['tenant_id', 'slug']);
        });

        // 2. Bodegas / Almacenes (Santa Cruz, La Paz, Cochabamba, etc.)
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('provider_id')->nullable(); // Si es bodega propia de un proveedor o bodega general Dropana
            $table->string('name', 120);
            $table->string('code', 30);
            $table->string('department', 50); // Santa Cruz, La Paz, Cochabamba, etc.
            $table->string('city', 100);
            $table->string('address', 255);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['tenant_id', 'code']);
        });

        // 3. Productos del Catálogo General / Megabodega
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('provider_id'); // Usuario con rol provider
            $table->uuid('category_id')->nullable();
            $table->string('name', 200);
            $table->string('slug', 220);
            $table->string('sku', 50);
            $table->text('description')->nullable();
            $table->decimal('cost_price_bob', 12, 2); // Precio que cobra el proveedor en BOB
            $table->decimal('suggested_price_bob', 12, 2); // Precio sugerido de venta en tienda
            $table->decimal('min_price_bob', 12, 2); // Precio mínimo permitido para dropshippers
            $table->decimal('weight_kg', 8, 3)->default(0.500); // Para cálculo logístico
            $table->string('status', 30)->default('draft'); // draft, published, archived, under_review
            $table->boolean('is_cod_available')->default(true); // Disponible para contra entrega
            $table->json('images')->nullable();
            $table->json('attributes')->nullable(); // colores, tallas, etc.
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status', 'is_cod_available']);
        });

        // 4. Inventario por Bodega
        Schema::create('warehouse_inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('warehouse_id');
            $table->uuid('product_id');
            $table->integer('stock_physical')->default(0); // Existencia física
            $table->integer('stock_reserved')->default(0); // Apartado por pedidos en confirmación o despacho
            $table->integer('stock_available')->default(0); // Físico - Reservado
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['warehouse_id', 'product_id']);
        });

        // 5. Histórico y Auditoría de Movimientos de Inventario
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('warehouse_id');
            $table->uuid('product_id');
            $table->uuid('user_id')->nullable(); // Quién autorizó o causó el movimiento
            $table->string('movement_type', 30); // in_reception, out_dispatch, return_cancelled, adjustment
            $table->integer('quantity'); // Positivo o negativo
            $table->integer('previous_stock');
            $table->integer('new_stock');
            $table->string('reference_type', 50)->nullable(); // order, reception, manual_adjustment
            $table->uuid('reference_id')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('warehouse_inventories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('categories');
    }
};
