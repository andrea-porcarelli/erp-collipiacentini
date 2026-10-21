<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            // Numero progressivo del tentativo per quella invoice (1, 2, …).
            $table->unsignedInteger('attempt')->default(1);
            $table->string('provider', 50)->nullable();
            // Payload JSON spedito al provider.
            $table->longText('request_payload')->nullable();
            // HTTP status + body della risposta (per debugging).
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            // Messaggio di eccezione (se la chiamata è andata in throw prima della response).
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Un log è immutabile: solo created_at.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['invoice_id', 'attempt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_logs');
    }
};
