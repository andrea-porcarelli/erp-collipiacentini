<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_links', function (Blueprint $table) {
            $table->dropColumn('language_id');
        });

        Schema::table('product_faqs', function (Blueprint $table) {
            $table->dropColumn(['language_id', 'question', 'answer']);
        });
    }

    public function down(): void
    {
        Schema::table('product_links', function (Blueprint $table) {
            $table->unsignedInteger('language_id')->nullable()->after('product_id');
        });

        Schema::table('product_faqs', function (Blueprint $table) {
            $table->unsignedInteger('language_id')->nullable()->after('product_id');
            $table->text('question')->after('language_id');
            $table->text('answer')->after('question');
        });
    }
};
