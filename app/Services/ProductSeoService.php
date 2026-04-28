<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductPriceVariation;
use App\Models\ProductVariant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ProductSeoService
{
    private const TIMEZONE      = 'Europe/Rome';
    private const CACHE_TTL     = 86400; // 24h
    private const CACHE_PREFIX  = 'product_seo_events';

    public function __construct(
        private ProductAvailabilityService $availabilityService,
        private ProductKeywordsService $keywordsService,
    ) {}

    public static function forget(int $productId): void
    {
        Cache::forget(self::cacheKey($productId));
    }

    private static function cacheKey(int $productId): string
    {
        return self::CACHE_PREFIX . ':' . $productId;
    }

    public function forListing(Partner $partner, Collection $products): array
    {
        return [
            'meta'   => $this->buildListingMeta($partner, $products),
            'jsonLd' => $this->buildListingJsonLd($partner, $products),
        ];
    }

    public function forProduct(Product $product, int $days = 60): array
    {
        return [
            'meta'   => $this->buildProductMeta($product),
            'jsonLd' => $this->buildProductJsonLd($product, $days),
        ];
    }

    private function buildListingMeta(Partner $partner, Collection $products): array
    {
        $partnerName = $partner->partner_name ?? 'Shop';
        $title       = sprintf('Esperienze e biglietti %s - Prenota online', $partnerName);
        $description = sprintf(
            'Scopri e prenota online le esperienze di %s. %d prodotti disponibili con pagamento sicuro.',
            $partnerName,
            $products->count(),
        );

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => URL::to('/shop'),
            'keywords'    => $this->listingKeywords($partner, $products),
            'og' => [
                'type'        => 'website',
                'title'       => $title,
                'description' => $description,
                'url'         => URL::to('/shop'),
                'image'       => $this->partnerImage($partner) ?? $this->firstProductImage($products),
                'site_name'   => $partnerName,
            ],
            'twitter' => [
                'card'        => 'summary_large_image',
                'title'       => $title,
                'description' => $description,
            ],
        ];
    }

    private function buildProductMeta(Product $product): array
    {
        $title       = $product->meta_title ?: $product->label;
        $description = $product->meta_description
            ?: Str::limit(strip_tags((string) ($product->intro ?? $product->description ?? '')), 158);
        $url         = $product->route;
        $image       = $this->productImage($product);

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => $url,
            'keywords'    => $this->keywordsService->generate($product),
            'og' => [
                'type'        => 'product',
                'title'       => $title,
                'description' => $description,
                'url'         => $url,
                'image'       => $image,
                'site_name'   => $product->partner?->partner_name,
            ],
            'twitter' => [
                'card'        => 'summary_large_image',
                'title'       => $title,
                'description' => $description,
                'image'       => $image,
            ],
        ];
    }

    private function buildListingJsonLd(Partner $partner, Collection $products): array
    {
        $items = $products->values()->map(function (Product $p, int $i) use ($partner) {
            $price = (float) ($p->lowest_price_with_commission ?? 0);

            return [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'url'      => $p->route,
                'name'     => $p->meta_title ?: $p->label,
                'image'    => $this->productImage($p),
                'item'     => array_filter([
                    '@type'       => 'Product',
                    'name'        => $p->meta_title ?: $p->label,
                    'description' => $p->intro ?? $p->description,
                    'image'       => $this->productImage($p),
                    'url'         => $p->route,
                    'brand'       => $partner->partner_name ? ['@type' => 'Brand', 'name' => $partner->partner_name] : null,
                    'offers'      => $price > 0 ? [
                        '@type'         => 'AggregateOffer',
                        'priceCurrency' => 'EUR',
                        'lowPrice'      => number_format($price, 2, '.', ''),
                        'availability'  => $p->is_available
                            ? 'https://schema.org/InStock'
                            : 'https://schema.org/OutOfStock',
                        'url'           => $p->route,
                    ] : null,
                ]),
            ];
        })->all();

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'itemListElement' => $items,
        ];
    }

    private function buildProductJsonLd(Product $product, int $days): array
    {
        $events = Cache::remember(
            self::cacheKey($product->id),
            self::CACHE_TTL,
            fn() => $this->buildEvents($product, $days),
        );

        return [
            '@context' => 'https://schema.org',
            '@graph'   => array_merge(
                [$this->buildProductNode($product)],
                $events,
            ),
        ];
    }

    private function buildProductNode(Product $product): array
    {
        $price = (float) ($product->lowest_price_with_commission ?? 0);

        return array_filter([
            '@type'       => 'Product',
            '@id'         => $product->route . '#product',
            'name'        => $product->meta_title ?: $product->label,
            'description' => $product->intro ?? $product->description,
            'image'       => $this->productImage($product),
            'url'         => $product->route,
            'brand'       => $product->partner?->partner_name
                ? ['@type' => 'Brand', 'name' => $product->partner->partner_name]
                : null,
            'offers'      => $price > 0 ? [
                '@type'         => 'AggregateOffer',
                'priceCurrency' => 'EUR',
                'lowPrice'      => number_format($price, 2, '.', ''),
                'availability'  => $product->is_available
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url'           => $product->route,
            ] : null,
        ]);
    }

    private function buildEvents(Product $product, int $days): array
    {
        $product->loadMissing(['variants.prices', 'partner']);

        $events    = [];
        $today     = CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $variantsByAvailability     = $product->variants->groupBy('availability_id');
        $variantsBySpecial          = $product->variants->groupBy('special_schedule_id');
        $genericVariants            = $product->variants
            ->whereNull('availability_id')
            ->whereNull('special_schedule_id');

        for ($i = 0; $i < $days; $i++) {
            $date = $today->addDays($i);
            $dateStr = $date->toDateString();

            if ($this->availabilityService->isDateClosed($product, $dateStr)) {
                continue;
            }

            $slots = $this->availabilityService->getSlotsForDate($product, $dateStr);
            if ($slots->isEmpty()) {
                continue;
            }

            $variation = $this->availabilityService->getApplicablePriceVariation($product, $dateStr);

            foreach ($slots as $slot) {
                $variants = $this->variantsForSlot(
                    $slot,
                    $variantsByAvailability,
                    $variantsBySpecial,
                    $genericVariants,
                );

                if ($variants->isEmpty()) {
                    continue;
                }

                $events[] = $this->buildEvent($product, $date, $slot, $variants, $variation);
            }
        }

        return $events;
    }

    private function variantsForSlot(
        array $slot,
        Collection $variantsByAvailability,
        Collection $variantsBySpecial,
        Collection $genericVariants,
    ): Collection {
        $variants = $slot['slot_type'] === 'weekly'
            ? $variantsByAvailability->get($slot['slot_id'], collect())
            : $variantsBySpecial->get($slot['slot_id'], collect());

        return $variants->isEmpty() ? $genericVariants : $variants;
    }

    private function buildEvent(
        Product $product,
        CarbonImmutable $date,
        array $slot,
        Collection $variants,
        ?ProductPriceVariation $variation,
    ): array {
        [$h, $m] = array_pad(explode(':', $slot['time']), 2, 0);
        $start   = $date->setTime((int) $h, (int) $m);
        $endDate = $this->computeEndDate($product, $start);

        $availabilityUri = is_null($slot['availability']) || $slot['availability'] > 0
            ? 'https://schema.org/InStock'
            : 'https://schema.org/SoldOut';

        $offers = $variants->map(fn(ProductVariant $v) => array_filter([
            '@type'         => 'Offer',
            'name'          => $v->label,
            'price'         => number_format($this->variantCommissionedPrice($product, $v, $variation), 2, '.', ''),
            'priceCurrency' => 'EUR',
            'availability'  => $availabilityUri,
            'url'           => $product->route,
            'validFrom'     => $start->toIso8601String(),
        ]))->values()->all();

        return array_filter([
            '@type'               => 'Event',
            'name'                => sprintf('%s - %s %s', $product->meta_title ?: $product->label, $date->toDateString(), $slot['time']),
            'startDate'           => $start->toIso8601String(),
            'endDate'             => $endDate?->toIso8601String(),
            'eventStatus'         => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location'            => [
                '@type' => 'Place',
                'name'  => $product->partner?->partner_name ?? '',
            ],
            'image'               => $this->productImage($product),
            'url'                 => $product->route,
            'offers'              => $offers,
        ]);
    }

    private function computeEndDate(Product $product, CarbonImmutable $start): ?CarbonImmutable
    {
        $minutes = ((int) $product->duration_days) * 1440
            + ((int) $product->duration_hours) * 60
            + ((int) $product->duration_minutes);

        if ($minutes <= 0) {
            $minutes = (int) $product->duration;
        }

        return $minutes > 0 ? $start->addMinutes($minutes) : null;
    }

    private function variantCommissionedPrice(Product $product, ProductVariant $variant, ?ProductPriceVariation $variation): float
    {
        $price = $this->availabilityService->applyPriceVariation((float) $variant->full_price, $variation);
        return $price + ($product->partner?->resolvePresaleCommission($price) ?? 0);
    }

    private function productImage(Product $product): ?string
    {
        $media = $product->cover->first() ?? $product->gallery->first();
        return $media?->file_path ? asset('storage/' . $media->file_path) : null;
    }

    private function partnerImage(Partner $partner): ?string
    {
        $media = $partner->logo ?? $partner->cover;
        return $media?->file_path ? asset('storage/' . $media->file_path) : null;
    }

    private function firstProductImage(Collection $products): ?string
    {
        foreach ($products as $p) {
            $url = $this->productImage($p);
            if ($url) return $url;
        }
        return null;
    }

    private function listingKeywords(Partner $partner, Collection $products): string
    {
        $terms = array_filter(array_merge(
            [$partner->partner_name, 'prenotazione online', 'biglietti', 'esperienze'],
            $products->pluck('label')->filter()->take(10)->all(),
        ));

        return implode(', ', array_unique(array_map(fn($t) => trim($t), $terms)));
    }
}
