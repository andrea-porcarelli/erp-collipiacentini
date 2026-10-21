<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->decimal('commission_presale_threshold_high', 8, 2)->nullable()->after('commission_presale_high');
            $table->decimal('commission_presale_threshold_low', 8, 2)->nullable()->after('commission_presale_threshold_high');

            $table->string('commission_presale_low_vat', 20)->nullable()->after('commission_presale_low');
            $table->string('commission_presale_high_vat', 20)->nullable()->after('commission_presale_high');

            $table->string('commission_miticko_fixed_vat', 20)->nullable()->after('commission_miticko_fixed');
            $table->string('commission_miticko_variable_vat', 20)->nullable()->after('commission_miticko_variable');

            $table->dropColumn([
                'commission_presale_threshold',
                'commission_presale_vat',
                'commission_miticko_vat',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->decimal('commission_presale_threshold', 8, 2)->nullable()->default(7.00)->after('commission_presale_high');
            $table->string('commission_presale_vat', 20)->nullable()->after('commission_presale_high');
            $table->string('commission_miticko_vat', 20)->nullable()->after('commission_miticko_variable');

            $table->dropColumn([
                'commission_presale_threshold_high',
                'commission_presale_threshold_low',
                'commission_presale_low_vat',
                'commission_presale_high_vat',
                'commission_miticko_fixed_vat',
                'commission_miticko_variable_vat',
            ]);
        });
    }
};
