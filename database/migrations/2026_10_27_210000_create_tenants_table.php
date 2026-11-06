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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('company_name', 150);
            $table->string('legal_nit', 30); // NIT Bolivia
            $table->string('country_code', 2)->default('BO');
            $table->string('currency_code', 3)->default('BOB');
            $table->string('domain', 150)->nullable()->unique();
            $table->string('support_email', 150)->nullable();
            $table->string('support_phone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
