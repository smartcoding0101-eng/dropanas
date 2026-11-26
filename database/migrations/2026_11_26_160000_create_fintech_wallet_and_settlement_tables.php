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
        // 1. Configuración Financiera y Comisiones de la Plataforma (Modificables por el Admin)
        if (!Schema::hasTable('financial_settings')) {
            Schema::create('financial_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->decimal('seller_commission_bob', 10, 2)->default(5.00); // Comisión fija cobrada al vendedor
                $table->decimal('provider_commission_bob', 10, 2)->default(5.00); // Comisión fija cobrada al proveedor
                $table->string('bank_account_name', 150)->default('Dropana Bolivia S.R.L. / Cuenta Central');
                $table->string('bank_name', 100)->default('Banco Nacional de Bolivia / BCP');
                $table->string('bank_account_number', 80)->nullable();
                $table->string('qr_deposit_image_url', 255)->nullable();
                $table->text('recharge_instructions')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->unique('tenant_id');
            });
        }

        // 2. Solicitudes de Recarga de Saldo en Billetera (Vendedor -> Admin vía QR / Transferencia)
        if (!Schema::hasTable('wallet_topups')) {
            Schema::create('wallet_topups', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('user_id'); // Vendedor o usuario solicitante
                $table->uuid('wallet_id');
                $table->string('topup_number', 50)->unique();
                $table->decimal('amount_bob', 14, 2);
                $table->string('payment_method', 40)->default('qr_simple'); // qr_simple, bank_transfer, binance_pay
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_reference', 100)->nullable();
                $table->string('receipt_path', 255)->nullable();
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->text('admin_notes')->nullable();
                $table->uuid('approved_by_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
                $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
                $table->index(['tenant_id', 'status', 'created_at']);
            });
        }

        // 3. Rendición de Cajas y Lotes de Courier (Courier deposita a Dropana el efectivo de calle)
        if (!Schema::hasTable('carrier_cash_settlements')) {
            Schema::create('carrier_cash_settlements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('carrier_id'); // Courier o repartidor motorizado
                $table->string('settlement_number', 50)->unique();
                $table->decimal('total_collected_bob', 14, 2); // Total recaudado en calle
                $table->decimal('courier_fees_earned_bob', 14, 2); // Fletes correspondientes al chofer
                $table->decimal('net_deposit_bob', 14, 2); // Monto neto depositado a Dropana
                $table->string('payment_method', 40)->default('bank_transfer'); // bank_transfer, qr_simple, cash_handover
                $table->string('deposit_reference', 100)->nullable();
                $table->string('receipt_path', 255)->nullable();
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->uuid('approved_by_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->foreign('carrier_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
                $table->index(['tenant_id', 'status', 'created_at']);
            });
        }

        // 4. Agregar trazabilidad de prepago, comisiones duales y rendición a pedidos
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'platform_fee_seller_bob')) {
                $table->decimal('platform_fee_seller_bob', 10, 2)->default(5.00)->after('platform_fee_bob');
            }
            if (!Schema::hasColumn('orders', 'platform_fee_provider_bob')) {
                $table->decimal('platform_fee_provider_bob', 10, 2)->default(5.00)->after('platform_fee_seller_bob');
            }
            if (!Schema::hasColumn('orders', 'prepaid_at')) {
                $table->timestamp('prepaid_at')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('orders', 'carrier_cash_settlement_id')) {
                $table->uuid('carrier_cash_settlement_id')->nullable()->after('shipping_manifest_id');
                $table->foreign('carrier_cash_settlement_id')->references('id')->on('carrier_cash_settlements')->onDelete('set null');
            }
            if (!Schema::hasColumn('orders', 'cod_reimbursed_at')) {
                $table->timestamp('cod_reimbursed_at')->nullable()->after('settled_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'carrier_cash_settlement_id')) {
                $table->dropForeign(['carrier_cash_settlement_id']);
                $table->dropColumn('carrier_cash_settlement_id');
            }
            $columnsToDrop = array_filter([
                'platform_fee_seller_bob',
                'platform_fee_provider_bob',
                'prepaid_at',
                'cod_reimbursed_at',
            ], fn($col) => Schema::hasColumn('orders', $col));

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::dropIfExists('carrier_cash_settlements');
        Schema::dropIfExists('wallet_topups');
        Schema::dropIfExists('financial_settings');
    }
};
