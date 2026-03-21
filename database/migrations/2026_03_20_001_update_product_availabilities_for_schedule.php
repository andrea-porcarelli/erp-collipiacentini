<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_availabilities', function (Blueprint $table) {
            $table->tinyInteger('day_of_week')->unsigned()->nullable()->after('product_id')->comment('1=Lunedì … 7=Domenica. Null = record per data specifica.');
            $table->dropColumn('date');
            $table->integer('availability')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_availabilities', function (Blueprint $table) {
            $table->dropColumn('day_of_week');
            $table->date('date')->nullable(false)->change();
            $table->integer('availability')->nullable(false)->change();
        });
    }
};
