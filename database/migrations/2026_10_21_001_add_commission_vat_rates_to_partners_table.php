<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->decimal('commission_presale_vat', 5, 2)->nullable()->after('commission_presale_high');
            $table->decimal('commission_miticko_vat', 5, 2)->nullable()->after('commission_miticko_variable');
            $table->decimal('commission_payment_vat', 5, 2)->nullable()->after('commission_payment');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'commission_presale_vat',
                'commission_miticko_vat',
                'commission_payment_vat',
            ]);
        });
    }
};
