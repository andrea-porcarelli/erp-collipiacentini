<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $icons = [
            'VISGU' => 'fa-regular fa-flag-pennant',
            'VISLI' => 'fa-regular fa-eye',
            'DEGUS' => 'fa-regular fa-wine-glass',
            'DEGVI' => 'fa-regular fa-grapes',
            'PRANZ' => 'fa-regular fa-bowl-hot',
            'CENA'  => 'fa-regular fa-utensils',
            'PRAVI' => 'fa-regular fa-grill-hot',
            'CENVI' => 'fa-regular fa-plate-utensils',
            'SPETT' => 'fa-regular fa-popcorn',
            'SPORT' => 'fa-regular fa-bicycle',
            'PERNO' => 'fa-regular fa-bed-front',
        ];

        foreach ($icons as $code => $icon) {
            DB::table('categories')->where('category_code', $code)->update(['icon' => $icon]);
        }

        $newCategories = [
            ['category_code' => 'DISCO', 'label' => 'Discoteca',  'icon' => 'fa-regular fa-music'],
            ['category_code' => 'APERI', 'label' => 'Aperitivo', 'icon' => 'fa-regular fa-martini-glass-citrus'],
        ];

        foreach ($newCategories as $category) {
            $exists = DB::table('categories')
                ->where('label', $category['label'])
                ->orWhere('category_code', $category['category_code'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('categories')->insert([
                'is_active' => 1,
                'iva' => 22.00,
                'category_code' => $category['category_code'],
                'label' => $category['label'],
                'icon' => $category['icon'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive: non rimuoviamo categorie o icone già assegnate per evitare FK violations.
    }
};
