<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variant_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('vat_rate_id')->nullable()->after('price');
            $table->foreign('vat_rate_id')->references('id')->on('vat_rates')->nullOnDelete();
        });

        // Backfill: mappa i vat_rate numerici esistenti verso l'id in vat_rates.
        $vatMap = DB::table('vat_rates')->whereNull('nature')->pluck('id', 'rate');
        foreach ($vatMap as $rate => $id) {
            DB::table('product_variant_prices')
                ->whereRaw('ROUND(vat_rate, 2) = ?', [(float) $rate])
                ->update(['vat_rate_id' => $id]);
        }

        // Fallback: tutte le righe che non hanno trovato corrispondenza puntano all'aliquota 0% / esente.
        $defaultId = DB::table('vat_rates')->where('code', '0')->value('id');
        if ($defaultId) {
            DB::table('product_variant_prices')
                ->whereNull('vat_rate_id')
                ->update(['vat_rate_id' => $defaultId]);
        }

        Schema::table('product_variant_prices', function (Blueprint $table) {
            $table->dropColumn('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('product_variant_prices', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->default(22.00)->after('price');
        });

        // Ripopola la colonna numerica dal join con vat_rates.
        DB::statement('UPDATE product_variant_prices pvp
            INNER JOIN vat_rates vr ON pvp.vat_rate_id = vr.id
            SET pvp.vat_rate = COALESCE(vr.rate, 0)');

        Schema::table('product_variant_prices', function (Blueprint $table) {
            $table->dropForeign(['vat_rate_id']);
            $table->dropColumn('vat_rate_id');
        });
    }
};
