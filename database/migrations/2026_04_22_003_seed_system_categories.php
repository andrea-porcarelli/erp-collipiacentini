<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $categories = [
            ['category_code' => 'VISGU', 'label' => 'Visita guidata'],
            ['category_code' => 'VISLI', 'label' => 'Visita libera'],
            ['category_code' => 'DEGUS', 'label' => 'Degustazione'],
            ['category_code' => 'DEGVI', 'label' => 'Degustazione e visita'],
            ['category_code' => 'PRANZ', 'label' => 'Pranzo'],
            ['category_code' => 'CENA',  'label' => 'Cena'],
            ['category_code' => 'PRAVI', 'label' => 'Pranzo e visita'],
            ['category_code' => 'CENVI', 'label' => 'Cena e visita'],
            ['category_code' => 'SPETT', 'label' => 'Spettacolo'],
            ['category_code' => 'SPORT', 'label' => 'Sport ed esperienze'],
            ['category_code' => 'PERNO', 'label' => 'Pernottamento'],
        ];

        foreach ($categories as $category) {
            $exists = DB::table('categories')
                ->where('label', $category['label'])
                ->orWhere('category_code', $category['category_code'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('categories')->insert([
                'is_active'     => 1,
                'iva'           => 22.00,
                'category_code' => $category['category_code'],
                'label'         => $category['label'],
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive migration: le categorie possono essere usate da prodotti esistenti.
        // Non rimuoviamo nulla in down() per evitare FK violations.
    }
};
