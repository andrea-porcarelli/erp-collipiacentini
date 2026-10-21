<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->string('partner_commission_presale_vat', 20)->nullable()->after('partner_commission_presale_threshold');
            $table->string('partner_commission_miticko_vat', 20)->nullable()->after('partner_commission_miticko_variable');
            $table->string('partner_commission_payment_vat', 20)->nullable()->after('partner_commission_payment');
        });
    }

    public function down(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->dropColumn([
                'partner_commission_presale_vat',
                'partner_commission_miticko_vat',
                'partner_commission_payment_vat',
            ]);
        });
    }
};
