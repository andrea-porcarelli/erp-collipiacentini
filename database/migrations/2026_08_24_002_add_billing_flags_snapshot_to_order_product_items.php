<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->boolean('bill_ticket_base')->nullable()->after('partner_commission_payment');
            $table->boolean('bill_presale')->nullable()->after('bill_ticket_base');
            $table->boolean('bill_miticko_commission')->nullable()->after('bill_presale');
            $table->boolean('bill_bank_commission')->nullable()->after('bill_miticko_commission');
        });
    }

    public function down(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->dropColumn([
                'bill_ticket_base',
                'bill_presale',
                'bill_miticko_commission',
                'bill_bank_commission',
            ]);
        });
    }
};
