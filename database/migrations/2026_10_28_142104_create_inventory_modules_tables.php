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
        // ===== MÓDULO 1 — INVENTARIO AVANZADO =====
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('provider_id');
            $table->uuid('warehouse_id');
            $table->string('po_number', 50)->unique();
            $table->string('status', 30)->default('draft'); // draft, confirmed, receiving, received, cancelled
            $table->string('notes', 500)->nullable();
            $table->timestamp('expected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id');
            $table->uuid('product_id');
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost_bob', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->uuid('from_warehouse_id');
            $table->uuid('to_warehouse_id');
            $table->uuid('requested_by');
            $table->uuid('approved_by')->nullable();
            $table->integer('quantity');
            $table->string('status', 30)->default('pending'); // pending, in_transit, completed, cancelled
            $table->string('notes', 500)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('inventory_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('warehouse_id');
            $table->uuid('product_id');
            $table->integer('min_stock_threshold')->default(5);
            $table->integer('reorder_quantity')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['warehouse_id', 'product_id']);
        });

        // ===== MÓDULO 2 — REPORTES Y ANALYTICS =====
        Schema::create('daily_metrics_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->date('snapshot_date');
            $table->integer('total_orders')->default(0);
            $table->integer('confirmed_orders')->default(0);
            $table->integer('delivered_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->decimal('gross_revenue_bob', 14, 2)->default(0);
            $table->decimal('platform_fees_bob', 14, 2)->default(0);
            $table->decimal('dropshipper_profits_bob', 14, 2)->default(0);
            $table->decimal('provider_revenue_bob', 14, 2)->default(0);
            $table->integer('active_dropshippers')->default(0);
            $table->integer('top_product_units_sold')->default(0);
            $table->uuid('top_product_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'snapshot_date']);
            $table->index(['tenant_id', 'snapshot_date']);
        });

        // ===== MÓDULO 3 — NOTIFICACIONES =====
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id')->nullable();
            $table->string('channel', 30); // email, sms, whatsapp, push, in_app
            $table->string('event_type', 60);
            $table->string('recipient', 200);
            $table->string('subject', 200)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('sent');
            $table->json('metadata')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'event_type', 'created_at']);
            $table->index(['user_id', 'status']);
        });

        // ===== MÓDULO 4 — GESTIÓN DE CLIENTES (CRM) =====
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('full_name', 150);
            $table->string('phone', 30)->index();
            $table->string('document_id', 40)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('department', 60)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('default_address', 300)->nullable();
            $table->integer('total_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->decimal('total_spent_bob', 14, 2)->default(0);
            $table->string('customer_segment', 30)->default('new'); // new, regular, vip, at_risk
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'phone']);
            $table->index(['tenant_id', 'customer_segment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('daily_metrics_snapshots');
        Schema::dropIfExists('inventory_alerts');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
