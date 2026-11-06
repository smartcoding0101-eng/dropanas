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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('delivery_attempts')->default(0)->after('status');
            $table->string('novedad_reason', 60)->nullable()->after('delivery_attempts'); // cliente_ausente, telefono_apagado, direccion_incompleta, rechazo_sin_dinero, reprogramado
            $table->text('novedad_notes')->nullable()->after('novedad_reason');
            $table->dateTime('rescheduled_delivery_at')->nullable()->after('novedad_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_attempts', 'novedad_reason', 'novedad_notes', 'rescheduled_delivery_at']);
        });
    }
};
