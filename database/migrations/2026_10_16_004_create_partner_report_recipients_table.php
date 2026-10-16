<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_report_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_report_id')
                ->constrained('partner_reports')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('email');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['partner_report_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_report_recipients');
    }
};
