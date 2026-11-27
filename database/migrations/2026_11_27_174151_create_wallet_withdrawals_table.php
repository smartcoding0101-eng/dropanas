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
        if (!Schema::hasTable('wallet_withdrawals')) {
            Schema::create('wallet_withdrawals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('user_id'); // Dropshipper o solicitante
                $table->uuid('wallet_id');
                $table->string('withdrawal_number', 50)->unique();
                $table->decimal('amount_bob', 14, 2);
                $table->string('payout_method', 40)->default('qr_simple'); // qr_simple, bank_transfer, binance_pay
                $table->string('beneficiary_name', 150);
                $table->string('document_id', 40)->nullable(); // Carnet de Identidad (CI)
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 80)->nullable();
                $table->string('binance_pay_id', 80)->nullable();
                $table->string('qr_code_path', 255)->nullable(); // Imagen del QR del vendedor para pagarle
                $table->string('status', 30)->default('pending'); // pending, completed, rejected
                $table->string('transfer_receipt_path', 255)->nullable(); // Comprobante de pago emitido por el admin
                $table->string('transfer_reference', 100)->nullable();
                $table->text('admin_notes')->nullable();
                $table->uuid('processed_by_id')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
                $table->foreign('processed_by_id')->references('id')->on('users')->onDelete('set null');
                $table->index(['tenant_id', 'status', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_withdrawals');
    }
};
