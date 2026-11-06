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
        Schema::create('bank_settlement_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('processed_by_id')->nullable(); // Tenant Admin que ejecutó o autorizó
            $table->string('batch_number', 50)->unique(); // Ej: LOT-BNB-20261028-001
            $table->string('bank_name', 100); // Banco Nacional de Bolivia (BNB), Banco Unión, Banco de Crédito BCP, Banco Mercantil Santa Cruz, QR Simple Interoperable
            $table->unsignedInteger('total_withdrawals_count')->default(0);
            $table->decimal('total_amount_bob', 14, 2)->default(0.00);
            $table->string('status', 30)->default('pending'); // pending, processing, completed, rejected
            $table->string('bank_reference_number', 100)->nullable(); // Número de comprobante bancario
            $table->string('receipt_file_path', 255)->nullable(); // Comprobante o voucher QR subido
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('processed_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['tenant_id', 'bank_name', 'status']);
        });

        // Relacionar wallet_transactions con el lote bancario si es un retiro
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->uuid('bank_settlement_batch_id')->nullable()->after('metadata');
            $table->foreign('bank_settlement_batch_id')->references('id')->on('bank_settlement_batches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['bank_settlement_batch_id']);
            $table->dropColumn('bank_settlement_batch_id');
        });

        Schema::dropIfExists('bank_settlement_batches');
    }
};
