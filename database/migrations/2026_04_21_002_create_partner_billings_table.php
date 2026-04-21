<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')
                ->unique()
                ->constrained('partners')
                ->cascadeOnDelete();

            $table->string('legal_name')->nullable();
            $table->string('vat_number', 20)->nullable();
            $table->string('tax_code', 20)->nullable();

            $table->string('street_address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('province', 5)->nullable();
            $table->string('country', 2)->nullable()->default('IT');

            $table->string('pec_email')->nullable();
            $table->string('sdi_code', 7)->nullable();

            $table->string('iban', 34)->nullable();
            $table->string('tax_regime')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_billings');
    }
};
