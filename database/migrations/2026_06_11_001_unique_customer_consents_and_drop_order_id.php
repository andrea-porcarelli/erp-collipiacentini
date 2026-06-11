<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill: per ogni gruppo (customer_id, partner_consent_id) con più
        // record, teniamo quello con expires_at MAX (consenso più "fresco");
        // fallback a subscribed_at più recente, poi id MAX.
        $duplicates = DB::table('customer_consents')
            ->select('customer_id', 'partner_consent_id')
            ->groupBy('customer_id', 'partner_consent_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $keepId = DB::table('customer_consents')
                ->where('customer_id', $dup->customer_id)
                ->where('partner_consent_id', $dup->partner_consent_id)
                ->orderByRaw('expires_at IS NULL ASC')
                ->orderByDesc('expires_at')
                ->orderByDesc('subscribed_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('customer_consents')
                ->where('customer_id', $dup->customer_id)
                ->where('partner_consent_id', $dup->partner_consent_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        // Step 1: rilascia la FK su order_id (libera l'index composito).
        Schema::table('customer_consents', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        // Step 2: ora possiamo droppare index + colonna e aggiungere l'unique.
        Schema::table('customer_consents', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'partner_consent_id']);
            $table->dropColumn('order_id');
            $table->unique(['customer_id', 'partner_consent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_consents', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'partner_consent_id']);
            $table->foreignId('order_id')
                ->nullable()
                ->after('partner_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->index(['order_id', 'partner_consent_id']);
        });
    }
};
