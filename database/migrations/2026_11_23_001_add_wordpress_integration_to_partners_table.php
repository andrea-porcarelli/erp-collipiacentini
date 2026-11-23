<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('wordpress_endpoint')->nullable()->after('token');
            $table->string('wordpress_webhook_secret', 64)->nullable()->after('wordpress_endpoint');
            $table->boolean('wordpress_enabled')->default(false)->after('wordpress_webhook_secret');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['wordpress_endpoint', 'wordpress_webhook_secret', 'wordpress_enabled']);
        });
    }
};
