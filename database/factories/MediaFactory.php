<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mediable_type' => Product::class,
            'mediable_id' => 5,
            'media_type' => 'image',
            'file_name' => fake()->word() . '.jpg',
            'file_path' => 'images/products/' . fake()->uuid() . '.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(50000, 500000),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Create media for partner with id = 2
     */
    public function forPartner(): static
    {
        return $this->state(fn (array $attributes) => [
            'mediable_type' => Partner::class,
            'mediable_id' => 2,
            'file_path' => 'images/partners/' . fake()->uuid() . '.jpg',
            'description' => 'Immagine default per il partner',
        ]);
    }

    /**
     * Create media for product with id = 5
     */
    public function forProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'mediable_type' => Product::class,
            'mediable_id' => 5,
            'file_path' => 'images/products/' . fake()->uuid() . '.jpg',
            'description' => 'Immagine default per il prodotto',
        ]);
    }

    /**
     * Create a logo image
     */
    public function logo(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'logo',
            'description' => 'Logo',
        ]);
    }

    /**
     * Create a gallery image
     */
    public function gallery(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'gallery',
            'description' => 'Immagine galleria',
        ]);
    }

    /**
     * Create a cover image
     */
    public function cover(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'cover',
            'description' => 'Immagine di copertina',
        ]);
    }
}
