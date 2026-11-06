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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->string('role', 30)->default('dropshipper')->after('email'); // superadmin, tenant_admin, provider, dropshipper, carrier_agent
            $table->string('phone', 30)->nullable()->after('role'); // Formato Bolivia +591
            $table->string('document_id', 30)->nullable()->after('phone'); // CI o NIT del usuario
            $table->string('company_or_store_name', 150)->nullable()->after('document_id');
            $table->string('city', 100)->nullable()->after('company_or_store_name'); // Santa Cruz, La Paz, Cochabamba, etc.
            $table->string('department', 50)->nullable()->after('city');
            $table->boolean('is_active')->default(true)->after('department');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'role']);
            $table->dropColumn([
                'tenant_id',
                'role',
                'phone',
                'document_id',
                'company_or_store_name',
                'city',
                'department',
                'is_active',
            ]);
        });
    }
};
