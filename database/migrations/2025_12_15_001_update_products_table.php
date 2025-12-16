<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('duration')->nullable()->after('meta_keywords');
            $table->enum('product_type', ['free', 'guided'])->default('free')->nullable()->after('duration');
            $table->text('intro')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('duration');
            $table->dropColumn('product_type');
            $table->dropColumn('intro');
        });
    }
};
