<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('partner_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('partners')
                ->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('partner_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('partners')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn('partner_id');
            $table->foreignId('company_id')->nullable()->after('customer_id')->constrained('companies')->cascadeOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn('partner_id');
            $table->integer('company_id')->after('customer_id');
        });
    }
};
