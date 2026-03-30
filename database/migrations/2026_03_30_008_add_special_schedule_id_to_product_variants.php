<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('special_schedule_id')
                ->nullable()
                ->after('availability_id')
                ->constrained('product_special_schedules')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['special_schedule_id']);
            $table->dropColumn('special_schedule_id');
        });
    }
};
