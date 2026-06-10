<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::table('customer_consents', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'partner_consent_id']);
            $table->dropConstrainedForeignId('order_id');
            $table->unique(['customer_id', 'partner_consent_id']);
        });
    }
};
