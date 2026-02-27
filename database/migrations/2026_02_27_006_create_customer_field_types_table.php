<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_field_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // identificatore macchina (es. "address")
            $table->string('label');            // etichetta visibile (es. "Indirizzo di residenza")
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_field_types');
    }
};
