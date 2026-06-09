<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')
                ->constrained('partners')
                ->cascadeOnDelete();
            $table->string('code', 64)->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('expiry_days')->default(0);
            $table->unsignedInteger('expiry_months')->default(0);
            $table->unsignedInteger('expiry_years')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['partner_id', 'code']);
            $table->index(['partner_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_consents');
    }
};
