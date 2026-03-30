<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Remove old FK constraint before dropping column
            $table->dropForeign(['product_availability_id']);
            $table->dropColumn([
                'product_availability_id',
                'quantity_full',
                'quantity_reduced',
                'quantity_free',
                'price_full',
                'price_reduced',
                'price_free',
            ]);

            // New columns
            $table->string('slot_type')->nullable()->after('time'); // 'weekly' | 'special'
            $table->unsignedBigInteger('slot_id')->nullable()->after('slot_type');
            $table->foreignId('applied_price_variation_id')
                ->nullable()
                ->after('slot_id')
                ->constrained('product_price_variations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['applied_price_variation_id']);
            $table->dropColumn(['slot_type', 'slot_id', 'applied_price_variation_id']);

            $table->foreignId('product_availability_id')->nullable()->constrained('product_availabilities')->cascadeOnDelete();
            $table->integer('quantity_full')->default(0);
            $table->integer('quantity_reduced')->default(0);
            $table->integer('quantity_free')->default(0);
            $table->decimal('price_full', 10, 2)->default(0);
            $table->decimal('price_reduced', 10, 2)->default(0);
            $table->decimal('price_free', 10, 2)->default(0);
        });
    }
};
