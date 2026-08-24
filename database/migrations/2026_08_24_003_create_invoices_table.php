<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('recipient_type', ['partner', 'customer']);
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();

            // Anagrafica destinatario congelata al momento dell'emissione.
            $table->string('recipient_name')->nullable();
            $table->string('recipient_vat_number')->nullable();
            $table->string('recipient_tax_code')->nullable();
            $table->string('recipient_address')->nullable();
            $table->string('recipient_postal_code')->nullable();
            $table->string('recipient_city')->nullable();
            $table->string('recipient_province', 10)->nullable();
            $table->string('recipient_country', 3)->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_pec')->nullable();
            $table->string('recipient_sdi_code', 10)->nullable();

            $table->string('number')->nullable();
            $table->json('lines');
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('EUR');

            // draft: creata localmente, non inviata al provider
            // queued: pronta per l'invio al provider
            // sent: inviata al provider, in attesa esito
            // accepted / rejected: esito SDI
            // error: errore locale o provider
            $table->enum('status', ['draft', 'queued', 'sent', 'accepted', 'rejected', 'error'])->default('draft');
            $table->string('provider', 40)->nullable();
            $table->string('provider_id')->nullable();
            $table->string('provider_uuid')->nullable();
            $table->text('provider_error')->nullable();
            $table->longText('provider_payload')->nullable();

            $table->timestamp('emitted_at')->nullable();
            $table->foreignId('emitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'recipient_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
