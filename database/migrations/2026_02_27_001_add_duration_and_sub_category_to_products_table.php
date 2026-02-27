<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->nullable()->after('duration');
            $table->unsignedInteger('duration_hours')->nullable()->after('duration_days');
            $table->unsignedInteger('duration_minutes')->nullable()->after('duration_hours');
            $table->unsignedInteger('sub_category_id')->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['duration_days', 'duration_hours', 'duration_minutes', 'sub_category_id']);
        });
    }
};
