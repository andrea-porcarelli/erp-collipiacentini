<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('partner_consent_id')
                ->constrained('partner_consents')
                ->restrictOnDelete();
            $table->foreignId('partner_id')
                ->constrained('partners')
                ->restrictOnDelete();
            $table->boolean('accepted')->default(false);
            $table->dateTime('subscribed_at');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'partner_consent_id']);
            $table->index(['partner_id', 'partner_consent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_consents');
    }
};
