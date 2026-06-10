<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();

            // Nullable per consentire log generati durante la fase carrello
            // (prima che l'ordine esista). Al checkout vengono "promossi"
            // settando order_id sui log con il cart_id corrispondente.
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('cart_id')->nullable()->index();
            $table->string('session_id', 64)->nullable()->index();

            $table->nullableMorphs('causer');
            $table->string('causer_name')->nullable();

            $table->string('event_type', 64)->index();
            $table->text('description');

            $table->json('properties')->nullable();
            $table->json('context')->nullable();

            $table->uuid('batch_uuid')->nullable()->index();

            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['order_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_logs');
    }
};
