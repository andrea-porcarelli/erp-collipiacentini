<?php

namespace Tests;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductAvailability;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed di base: partner + categoria + prodotto + variante + prezzo + availability
     * per un giorno futuro con uno slot alle 10:00.
     */
    protected function seedBaseline(?Carbon $bookingDate = null): array
    {
        $bookingDate = $bookingDate ?? Carbon::now()->addDays(30);
        $time = '10:00';

        $partner = Partner::create([
            'partner_name' => 'Rocca Test',
            'partner_code' => 'TT',
            'has_notify' => 'no',
            'email_notify' => 'notify@rocca.test',
            'is_active' => 1,
            'sale_method' => 'whitelabel_domain',
            'domain_name' => 'rocca.test',
            'slug_name' => 'rocca',
            'commission_presale_low' => 0,
            'commission_presale_high' => 0,
            'commission_presale_threshold' => 0,
            'commission_miticko_fixed' => 0,
            'commission_miticko_variable' => 0,
            'commission_payment' => 0,
            'consents_enabled' => false,
        ]);

        $category = Category::create([
            'is_active' => 1,
            'iva' => 22,
            'category_code' => 'V',
            'label' => 'Visita',
        ]);

        $product = Product::create([
            'partner_id' => $partner->id,
            'category_id' => $category->id,
            'is_active' => 1,
            'label' => 'Visita guidata test',
            'occupancy' => 50,
            'occupancy_for_price' => 1,
            'product_type' => 'guided',
            'max_tickets_per_session' => 20,
            'booking_deadline_minutes' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'label' => 'Intero',
            'max_quantity' => 20,
            'sort_order' => 1,
        ]);

        ProductVariantPrice::create([
            'product_variant_id' => $variant->id,
            'label' => 'Prezzo intero',
            'price' => 10.00,
            'vat_rate' => 22,
        ]);

        ProductAvailability::create([
            'product_id' => $product->id,
            'day_of_week' => $bookingDate->isoWeekday(),
            'time' => $time,
            'availability' => 20,
        ]);

        return [
            'partner' => $partner->fresh(),
            'category' => $category,
            'product' => $product->fresh(),
            'variant' => $variant->fresh(),
            'booking_date' => $bookingDate->format('Y-m-d'),
            'booking_time' => $time,
        ];
    }
}
