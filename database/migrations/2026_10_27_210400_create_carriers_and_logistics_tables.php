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
        // 1. Operadores Logísticos / Couriers en Bolivia
        Schema::create('carriers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name', 100); // DEX Bolivia, Cartero Courier, IDE Courier, etc.
            $table->string('code', 40); // dex_bolivia, cartero_courier, ide_courier
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 120)->nullable();
            $table->boolean('has_cod_service')->default(true); // Soporte contra entrega
            $table->string('integration_mode', 40)->default('manifest_file'); // manifest_file, b2b_webhook, direct_api
            $table->json('settings')->nullable(); // Configuración de credenciales, formatos de archivo
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'code']);
        });

        // 2. Coberturas Geográficas y Tarifas en Bolivia
        Schema::create('carrier_coverages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('carrier_id');
            $table->string('department', 50); // Santa Cruz, La Paz, Cochabamba, Oruro, Potosí, Chuquisaca, Tarija, Beni, Pando
            $table->string('city', 100);
            $table->string('zone_type', 30)->default('urban'); // urban, suburban, provincial, remote
            $table->decimal('base_shipping_bob', 10, 2); // Costo del flete en BOB
            $table->decimal('cod_fee_percentage', 5, 2)->default(0.00); // Comisión de cobro COD si aplica
            $table->integer('estimated_delivery_days')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('carrier_id')->references('id')->on('carriers')->onDelete('cascade');
            $table->index(['tenant_id', 'department', 'city']);
        });

        // 3. Guías y Manifiestos de Despacho
        Schema::create('shipping_manifests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('carrier_id');
            $table->string('manifest_number', 50)->unique();
            $table->date('manifest_date');
            $table->integer('total_packages')->default(0);
            $table->decimal('total_cod_amount_bob', 14, 2)->default(0);
            $table->string('status', 30)->default('generated'); // generated, dispatched, received_by_carrier, closed
            $table->string('export_file_path', 255)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('carrier_id')->references('id')->on('carriers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_manifests');
        Schema::dropIfExists('carrier_coverages');
        Schema::dropIfExists('carriers');
    }
};
