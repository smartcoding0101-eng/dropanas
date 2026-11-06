<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade la columna 'notes' a wallet_transactions para registrar observaciones
     * en retiros y en liquidaciones bancarias, y también asegura que la columna
     * bank_settlement_batch_id ya exista (creada por migración anterior).
     */
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Columna 'notes' para texto libre en transacciones de retiro/crédito
            if (! Schema::hasColumn('wallet_transactions', 'notes')) {
                $table->text('notes')->nullable()->after('metadata');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wallet_transactions', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
