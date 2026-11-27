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
        Schema::table('financial_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_settings', 'admin_notification_email')) {
                $table->string('admin_notification_email', 150)->nullable()->after('recharge_instructions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_settings', function (Blueprint $table) {
            if (Schema::hasColumn('financial_settings', 'admin_notification_email')) {
                $table->dropColumn('admin_notification_email');
            }
        });
    }
};
