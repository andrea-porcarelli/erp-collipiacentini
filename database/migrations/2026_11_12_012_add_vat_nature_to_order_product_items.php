<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->string('vat_nature')->nullable()->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('order_product_items', function (Blueprint $table) {
            $table->dropColumn('vat_nature');
        });
    }
};
