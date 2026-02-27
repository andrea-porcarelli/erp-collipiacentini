<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_customer_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('customer_field_type_id');
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'customer_field_type_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customer_field_type_id')->references('id')->on('customer_field_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_customer_fields');
    }
};
