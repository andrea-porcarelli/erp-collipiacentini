<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->decimal('commission_presale_low', 8, 2)->nullable()->after('email_notify');
            $table->decimal('commission_presale_high', 8, 2)->nullable()->after('commission_presale_low');
            $table->decimal('commission_miticko_fixed', 8, 2)->nullable()->after('commission_presale_high');
            $table->decimal('commission_miticko_variable', 8, 2)->nullable()->after('commission_miticko_fixed');
            $table->decimal('commission_payment', 8, 2)->nullable()->after('commission_miticko_variable');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'commission_presale_low',
                'commission_presale_high',
                'commission_miticko_fixed',
                'commission_miticko_variable',
                'commission_payment',
            ]);
        });
    }
};
