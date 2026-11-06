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
        Schema::create('store_integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('dropshipper_id'); // Usuario propietario de la tienda
            $table->string('platform', 30); // shopify, woocommerce
            $table->string('store_name', 150);
            $table->string('store_url', 255);
            $table->string('api_key', 255)->nullable();
            $table->string('api_secret', 255)->nullable();
            $table->string('webhook_secret', 100)->unique(); // Token para validar la firma del webhook
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_dispatch')->default(false); // Si despacha automáticamente a DEX Bolivia
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('dropshipper_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['tenant_id', 'dropshipper_id', 'platform']);
        });

        Schema::create('store_webhook_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('store_integration_id');
            $table->string('platform', 30);
            $table->string('event_type', 60); // orders/create, orders/updated, woocommerce_order_created
            $table->string('external_order_id', 100)->nullable();
            $table->uuid('order_id')->nullable(); // Pedido generado en Dropana si fue exitoso
            $table->string('status', 30)->default('received'); // received, processed, failed, ignored
            $table->text('error_message')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('store_integration_id')->references('id')->on('store_integrations')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->index(['store_integration_id', 'external_order_id', 'status'], 'idx_swl_store_order_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_webhook_logs');
        Schema::dropIfExists('store_integrations');
    }
};
