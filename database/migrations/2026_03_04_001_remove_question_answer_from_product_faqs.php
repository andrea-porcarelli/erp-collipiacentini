<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_faqs', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('product_faqs', 'question')) $columns[] = 'question';
            if (Schema::hasColumn('product_faqs', 'answer'))   $columns[] = 'answer';
            if (!empty($columns)) $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        Schema::table('product_faqs', function (Blueprint $table) {
            $table->text('question')->after('product_id');
            $table->text('answer')->after('question');
        });
    }
};
