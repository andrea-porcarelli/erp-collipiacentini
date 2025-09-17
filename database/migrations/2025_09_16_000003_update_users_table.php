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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['god', 'admin', 'operator', 'partner'])->default('admin')->after('name');
            $table->integer('company_id')->nullable()->after('role');
            $table->integer('partner_id')->nullable()->after('company_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->dropColumn('company_id');
            $table->dropColumn('partner_id');
        });
    }
};
