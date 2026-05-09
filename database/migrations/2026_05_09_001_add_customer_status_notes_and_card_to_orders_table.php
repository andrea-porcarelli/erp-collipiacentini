<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_status', 30)->default('booked')->after('order_status');
            $table->text('customer_note')->nullable()->after('customer_status');
            $table->text('internal_note')->nullable()->after('customer_note');
            $table->string('card_brand', 20)->nullable()->after('stripe_payment_method');
            $table->string('card_last4', 4)->nullable()->after('card_brand');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'customer_status',
                'customer_note',
                'internal_note',
                'card_brand',
                'card_last4',
            ]);
        });
    }
};
