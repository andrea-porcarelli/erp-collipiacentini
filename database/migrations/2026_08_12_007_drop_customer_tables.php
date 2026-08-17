<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // customer_consents ha FK verso customers, va droppato prima.
        Schema::dropIfExists('customer_consents');
        Schema::dropIfExists('customers');
    }

    public function down(): void
    {
        // Down non ricrea le tabelle: la migration originale di creazione
        // resta a disposizione se serve un ripristino manuale.
    }
};
