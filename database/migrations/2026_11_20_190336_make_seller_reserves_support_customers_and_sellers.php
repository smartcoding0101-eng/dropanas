<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_seller_reserves', function (Blueprint $table) {
            $table->uuid('dropshipper_id')->nullable()->change();
            $table->uuid('customer_id')->nullable()->after('dropshipper_id');
            $table->string('target_type', 20)->default('seller')->after('customer_id')
                ->comment('seller: reserva a vendedor dropshipper, customer: reserva a cliente final');

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->index(['tenant_id', 'target_type']);
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_seller_reserves', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'target_type']);
            $table->uuid('dropshipper_id')->nullable(false)->change();
        });
    }
};
