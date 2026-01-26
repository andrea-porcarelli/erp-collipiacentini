<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Aggiorna enum role per includere 'customer'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('god', 'admin', 'operator', 'partner', 'customer') DEFAULT 'admin'");

        Schema::table('customers', function (Blueprint $table) {
            $table->string('fiscal_code', 16)->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('fiscal_code');
            $table->boolean('privacy_accepted')->default(false)->after('birth_date');
            $table->boolean('newsletter')->default(false)->after('privacy_accepted');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'fiscal_code',
                'birth_date',
                'privacy_accepted',
                'newsletter',
            ]);
        });
    }
};
