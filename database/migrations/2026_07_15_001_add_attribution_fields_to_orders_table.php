<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('utm_source', 191)->nullable()->after('paid_at');
            $table->string('utm_medium', 191)->nullable()->after('utm_source');
            $table->string('utm_campaign', 191)->nullable()->after('utm_medium');
            $table->string('utm_term', 191)->nullable()->after('utm_campaign');
            $table->string('utm_content', 191)->nullable()->after('utm_term');
            $table->string('referrer', 500)->nullable()->after('utm_content');
            $table->string('gclid', 255)->nullable()->after('referrer');
            $table->string('fbclid', 255)->nullable()->after('gclid');

            $table->index('utm_source');
            $table->index('utm_campaign');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['utm_source']);
            $table->dropIndex(['utm_campaign']);
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'referrer', 'gclid', 'fbclid',
            ]);
        });
    }
};
