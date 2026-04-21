<?php

namespace App\Services;

use App\Models\Product;

class ProductKeywordsService
{
    private const GENERIC_TERMS = [
        'prenotazione online',
        'biglietti',
        'esperienza',
    ];

    public function generate(Product $product): string
    {
        $terms = array_merge(
            array_filter([
                $product->label,
                $product->meta_title,
                $product->category?->label,
                $product->partner?->partner_name,
                $product->product_type,
            ]),
            self::GENERIC_TERMS,
        );

        $normalized = [];
        foreach ($terms as $term) {
            $key = mb_strtolower(trim($term));
            if ($key !== '') {
                $normalized[$key] = trim($term);
            }
        }

        return implode(', ', array_values($normalized));
    }
}
