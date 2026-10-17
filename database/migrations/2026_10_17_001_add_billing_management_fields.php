<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->enum('billing_managed_by', ['miticko', 'partner'])
                ->default('partner')
                ->after('report_auto_send_enabled');
            $table->string('billing_tool', 50)->nullable()->after('billing_managed_by');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('invoice_required')->default(false)->after('bill_bank_commission');
        });

        // Backfill: invoice_required = true dove uno dei 4 bill_* era già attivo.
        DB::statement('UPDATE products SET invoice_required = 1 WHERE bill_ticket_base = 1 OR bill_presale = 1 OR bill_miticko_commission = 1 OR bill_bank_commission = 1');

        Schema::table('invoices', function (Blueprint $table) {
            $table->text('description')->nullable()->after('number');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['billing_managed_by', 'billing_tool']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('invoice_required');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
