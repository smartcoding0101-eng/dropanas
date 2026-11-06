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
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->string('full_name', 150);
            $table->string('phone', 30); // WhatsApp Bolivia +591
            $table->string('email', 150)->nullable();
            $table->string('interest_role', 50); // dropshipper, provider, fulfillment, industrial_warehouse
            $table->string('estimated_monthly_volume', 50); // 1-50, 50-200, +200
            $table->string('department', 60)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('new'); // new, contacted, converted, discarded
            $table->timestamps();

            $table->index(['interest_role', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
