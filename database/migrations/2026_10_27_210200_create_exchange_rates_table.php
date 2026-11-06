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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(); // Null si es la tasa global del sistema
            $table->string('currency_pair', 10)->default('USD/BOB');
            $table->decimal('buy_rate', 10, 4);
            $table->decimal('sell_rate', 10, 4);
            $table->string('official_source', 100);
            $table->boolean('is_official')->default(true);
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['currency_pair', 'fetched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
