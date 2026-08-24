<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('bill_ticket_base')->default(true)->after('support_email');
            $table->boolean('bill_presale')->default(false)->after('bill_ticket_base');
            $table->boolean('bill_miticko_commission')->default(true)->after('bill_presale');
            $table->boolean('bill_bank_commission')->default(true)->after('bill_miticko_commission');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'bill_ticket_base',
                'bill_presale',
                'bill_miticko_commission',
                'bill_bank_commission',
            ]);
        });
    }
};
