<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_availability_id')->constrained('product_availabilities')->onDelete('cascade');
            $table->date('date');
            $table->time('time');
            $table->integer('quantity_full')->default(0);
            $table->integer('quantity_reduced')->default(0);
            $table->integer('quantity_free')->default(0);
            $table->decimal('price_full', 10, 2)->default(0);
            $table->decimal('price_reduced', 10, 2)->default(0);
            $table->decimal('price_free', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
