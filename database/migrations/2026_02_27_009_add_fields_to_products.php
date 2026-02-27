<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('occupancy')->default(0)->after('product_type');
            $table->boolean('occupancy_for_price')->default(false)->after('occupancy');
            $table->boolean('free_occupancy_rule')->default(false)->after('occupancy_for_price');
            $table->dropColumn(['linked_product_ids']);
        });

    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['occupancy', 'occupancy_for_price', 'free_occupancy_rule']);
        });

    }
};
