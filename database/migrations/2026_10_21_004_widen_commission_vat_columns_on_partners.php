<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            // Allargo da 10 a 20 char per ospitare le nature di fattura elettronica
            // (es. "N6.9" entra in 10, ma meglio dare margine per future estensioni).
            $table->string('commission_presale_vat', 20)->nullable()->change();
            $table->string('commission_miticko_vat', 20)->nullable()->change();
            $table->string('commission_payment_vat', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('commission_presale_vat', 10)->nullable()->change();
            $table->string('commission_miticko_vat', 10)->nullable()->change();
            $table->string('commission_payment_vat', 10)->nullable()->change();
        });
    }
};
