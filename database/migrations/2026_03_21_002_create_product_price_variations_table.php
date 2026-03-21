<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->enum('direction', ['increment', 'decrement'])->default('increment');
            $table->decimal('value', 10, 2);
            $table->enum('unit', ['euro', 'percent'])->default('euro');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_variations');
    }
};
