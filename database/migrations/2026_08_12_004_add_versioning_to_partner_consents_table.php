<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_consents', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('code');
            $table->dateTime('superseded_at')->nullable()->after('position');
            $table->foreignId('superseded_by_id')
                ->nullable()
                ->after('superseded_at')
                ->constrained('partner_consents')
                ->nullOnDelete();

            $table->dropUnique('partner_consents_partner_id_code_unique');

            $table->index(['partner_id', 'code', 'superseded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('partner_consents', function (Blueprint $table) {
            $table->dropIndex(['partner_id', 'code', 'superseded_at']);

            $table->dropForeign(['superseded_by_id']);
            $table->dropColumn(['version', 'superseded_at', 'superseded_by_id']);

            $table->unique(['partner_id', 'code'], 'partner_consents_partner_id_code_unique');
        });
    }
};
