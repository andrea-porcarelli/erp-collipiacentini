<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_prices');
    }

    public function down(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('reduced', 10, 2)->nullable();
            $table->decimal('free', 10, 2)->nullable();
            $table->integer('fee_number')->nullable();
            $table->decimal('fee_percent', 5, 2)->nullable();
            $table->timestamps();
        });
    }
};
