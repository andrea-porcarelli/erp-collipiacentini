<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['partner_id', 'category_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sub_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('partner_id')->after('id');
            $table->unsignedInteger('category_id')->nullable()->after('partner_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('sub_category_id')->nullable()->after('category_id');
        });
    }
};
