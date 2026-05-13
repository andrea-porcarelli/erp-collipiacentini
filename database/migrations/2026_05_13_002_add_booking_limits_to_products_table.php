<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_tickets_per_session')->nullable()->after('free_occupancy_rule');
            $table->unsignedSmallInteger('booking_deadline_hours')->nullable()->after('max_tickets_per_session');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['max_tickets_per_session', 'booking_deadline_hours']);
        });
    }
};
