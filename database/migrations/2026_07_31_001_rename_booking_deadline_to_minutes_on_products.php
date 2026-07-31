<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('booking_deadline_hours', 'booking_deadline_minutes');
        });

        DB::statement('UPDATE products SET booking_deadline_minutes = booking_deadline_minutes * 60 WHERE booking_deadline_minutes IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE products SET booking_deadline_minutes = CEIL(booking_deadline_minutes / 60) WHERE booking_deadline_minutes IS NOT NULL');

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('booking_deadline_minutes', 'booking_deadline_hours');
        });
    }
};
