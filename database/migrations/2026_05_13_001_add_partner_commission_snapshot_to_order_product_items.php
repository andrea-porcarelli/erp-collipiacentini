<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->decimal('partner_commission_presale_low', 8, 2)->nullable()->after('unit_price');
            $table->decimal('partner_commission_presale_high', 8, 2)->nullable()->after('partner_commission_presale_low');
            $table->decimal('partner_commission_presale_threshold', 8, 2)->nullable()->after('partner_commission_presale_high');
            $table->decimal('partner_commission_miticko_fixed', 8, 2)->nullable()->after('partner_commission_presale_threshold');
            $table->decimal('partner_commission_miticko_variable', 8, 2)->nullable()->after('partner_commission_miticko_fixed');
            $table->decimal('partner_commission_payment', 8, 2)->nullable()->after('partner_commission_miticko_variable');
        });
    }

    public function down(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->dropColumn([
                'partner_commission_presale_low',
                'partner_commission_presale_high',
                'partner_commission_presale_threshold',
                'partner_commission_miticko_fixed',
                'partner_commission_miticko_variable',
                'partner_commission_payment',
            ]);
        });
    }
};
