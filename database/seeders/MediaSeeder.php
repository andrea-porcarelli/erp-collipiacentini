<?php

namespace Database\Seeders;

use App\Models\Media;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea immagini per il Partner con id = 2
        Media::factory()
            ->forPartner()
            ->logo()
            ->create([
                'file_name' => 'partner-2-logo.jpg',
                'file_path' => 'images/partners/partner-2-logo.jpg',
            ]);

        Media::factory()
            ->forPartner()
            ->cover()
            ->create([
                'file_name' => 'partner-2-cover.jpg',
                'file_path' => 'images/partners/partner-2-cover.jpg',
            ]);

        // Crea immagini per la galleria del Partner con id = 2
        Media::factory()
            ->count(2)
            ->forPartner()
            ->gallery()
            ->create();

        // Crea immagini per il Product con id = 5
        Media::factory()
            ->forProduct()
            ->cover()
            ->create([
                'file_name' => 'product-5-cover.jpg',
                'file_path' => 'images/products/product-5-cover.jpg',
            ]);

        // Crea immagini per la galleria del Product con id = 5
        Media::factory()
            ->count(3)
            ->forProduct()
            ->gallery()
            ->create();
    }
}
