<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();
            $table->foreignId('partner_consent_id')
                ->constrained('partner_consents')
                ->cascadeOnDelete();
            $table->foreignId('partner_id')
                ->constrained('partners')
                ->cascadeOnDelete();
            $table->boolean('accepted')->default(false);
            $table->dateTime('subscribed_at');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'partner_consent_id']);
            $table->index(['customer_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_consents');
    }
};
