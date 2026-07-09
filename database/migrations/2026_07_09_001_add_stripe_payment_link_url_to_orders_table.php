<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('stripe_payment_link_id')->nullable()->after('payment_error');
            $table->string('stripe_payment_link_url', 500)->nullable()->after('stripe_payment_link_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_link_id', 'stripe_payment_link_url']);
        });
    }
};
