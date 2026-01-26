<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->foreignId('product_availability_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->date('booking_date')->nullable()->after('product_availability_id');
            $table->time('booking_time')->nullable()->after('booking_date');
            $table->integer('quantity_full')->default(0)->after('quantity');
            $table->integer('quantity_reduced')->default(0)->after('quantity_full');
            $table->integer('quantity_free')->default(0)->after('quantity_reduced');
            $table->decimal('price_full', 10, 2)->default(0)->after('quantity_free');
            $table->decimal('price_reduced', 10, 2)->default(0)->after('price_full');
            $table->decimal('price_free', 10, 2)->default(0)->after('price_reduced');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropForeign(['product_availability_id']);
            $table->dropColumn([
                'product_availability_id',
                'booking_date',
                'booking_time',
                'quantity_full',
                'quantity_reduced',
                'quantity_free',
                'price_full',
                'price_reduced',
                'price_free',
            ]);
        });
    }
};
