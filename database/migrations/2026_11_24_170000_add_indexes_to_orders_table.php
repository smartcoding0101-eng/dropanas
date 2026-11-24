<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade índices estratégicos para optimización de consultas de alta concurrencia en producción.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('tracking_number', 'orders_tracking_number_index');
            $table->index('customer_phone', 'orders_customer_phone_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_tracking_number_index');
            $table->dropIndex('orders_customer_phone_index');
        });
    }
};
