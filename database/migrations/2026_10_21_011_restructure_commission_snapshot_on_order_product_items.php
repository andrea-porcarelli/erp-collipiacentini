<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->decimal('partner_commission_presale_threshold_high', 8, 2)->nullable()->after('partner_commission_presale_high');
            $table->decimal('partner_commission_presale_threshold_low', 8, 2)->nullable()->after('partner_commission_presale_threshold_high');

            $table->string('partner_commission_presale_low_vat', 20)->nullable()->after('partner_commission_presale_low');
            $table->string('partner_commission_presale_high_vat', 20)->nullable()->after('partner_commission_presale_high');

            $table->string('partner_commission_miticko_fixed_vat', 20)->nullable()->after('partner_commission_miticko_fixed');
            $table->string('partner_commission_miticko_variable_vat', 20)->nullable()->after('partner_commission_miticko_variable');

            $table->dropColumn([
                'partner_commission_presale_threshold',
                'partner_commission_presale_vat',
                'partner_commission_miticko_vat',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->decimal('partner_commission_presale_threshold', 8, 2)->nullable()->after('partner_commission_presale_high');
            $table->string('partner_commission_presale_vat', 20)->nullable()->after('partner_commission_presale_high');
            $table->string('partner_commission_miticko_vat', 20)->nullable()->after('partner_commission_miticko_variable');

            $table->dropColumn([
                'partner_commission_presale_threshold_high',
                'partner_commission_presale_threshold_low',
                'partner_commission_presale_low_vat',
                'partner_commission_presale_high_vat',
                'partner_commission_miticko_fixed_vat',
                'partner_commission_miticko_variable_vat',
            ]);
        });
    }
};
