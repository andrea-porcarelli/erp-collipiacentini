<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('rate', 5, 2)->nullable();
            $table->string('nature')->nullable();
            $table->string('label');
            $table->tinyInteger('is_active')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $rows = [];
        $sort = 0;
        foreach (config('vat.rates', []) as $entry) {
            $code = (string) $entry['rate'];
            $isNature = (bool) preg_match('/^N/i', $code);
            $rows[] = [
                'code' => $code,
                'rate' => $isNature ? null : (float) $code,
                'nature' => $isNature ? strtoupper($code) : null,
                'label' => $entry['label'],
                'is_active' => 1,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if ($rows) {
            DB::table('vat_rates')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_rates');
    }
};
