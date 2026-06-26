<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    private const CACHE_TTL = 3600;

    public function index(Request $request): Response
    {
        $partner = $this->resolvePartner($request);

        $xml = Cache::remember(
            $this->cacheKey('index', $partner),
            self::CACHE_TTL,
            fn () => $this->buildIndex($partner),
        );

        return $this->xmlResponse($xml);
    }

    public function products(Request $request): Response
    {
        $partner = $this->resolvePartner($request);

        $xml = Cache::remember(
            $this->cacheKey('products', $partner),
            self::CACHE_TTL,
            fn () => $this->buildProducts($partner),
        );

        return $this->xmlResponse($xml);
    }

    public function pages(Request $request): Response
    {
        $partner = $this->resolvePartner($request);

        $xml = Cache::remember(
            $this->cacheKey('pages', $partner),
            self::CACHE_TTL,
            fn () => $this->buildPages($partner),
        );

        return $this->xmlResponse($xml);
    }

    private function buildIndex(Partner $partner): string
    {
        $products    = $partner->active_products()->select('id', 'updated_at')->get();
        $productsMod = $this->maxUpdated($products) ?? $partner->updated_at ?? Carbon::now();
        $pagesMod    = $partner->updated_at ?? Carbon::now();

        $entries = [
            ['loc' => URL::to('/sitemap-pages.xml'),    'lastmod' => $this->iso($pagesMod)],
            ['loc' => URL::to('/sitemap-products.xml'), 'lastmod' => $this->iso($productsMod)],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($entries as $entry) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1) . "</loc>\n";
            $xml .= '    <lastmod>' . $entry['lastmod'] . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    private function buildProducts(Partner $partner): string
    {
        $products = $partner->active_products()
            ->with(['cover', 'gallery'])
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
              . 'xmlns:image="http://www.google.com/schemas/sitemaps-image/1.1">' . "\n";

        foreach ($products as $product) {
            $loc = $product->route;
            if ($loc === '#' || empty($loc)) {
                continue;
            }

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
            $xml .= '    <lastmod>' . $this->iso($product->updated_at) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";

            foreach ($this->productImages($product) as $imageUrl) {
                $xml .= "    <image:image>\n";
                $xml .= '      <image:loc>' . htmlspecialchars($imageUrl, ENT_XML1) . "</image:loc>\n";
                $xml .= "    </image:image>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    private function buildPages(Partner $partner): string
    {
        $lastmod = $this->iso($partner->updated_at ?? Carbon::now());

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $xml .= "  <url>\n";
        $xml .= '    <loc>' . htmlspecialchars($this->homeUrl($partner), ENT_XML1) . "</loc>\n";
        $xml .= '    <lastmod>' . $lastmod . "</lastmod>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";

        foreach (array_keys(Partner::PAGES) as $page) {
            $url = $this->pageUrl($partner, $page);
            if (! $url) {
                continue;
            }

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
            $xml .= '    <lastmod>' . $lastmod . "</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.5</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    private function resolvePartner(Request $request): Partner
    {
        $partner = $request->get('partner');

        if (! $partner instanceof Partner) {
            abort(404);
        }

        return $partner;
    }

    private function homeUrl(Partner $partner): string
    {
        if ($partner->domain_name) {
            return URL::to('/');
        }

        return $partner->slug_name
            ? URL::to('/' . $partner->slug_name)
            : URL::to('/');
    }

    private function pageUrl(Partner $partner, string $page): ?string
    {
        try {
            if ($partner->domain_name) {
                return route('partner.page', ['page' => $page]);
            }

            if ($partner->slug_name) {
                return route('partner.page.slug', ['slug' => $partner->slug_name, 'page' => $page]);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function productImages(Product $product): array
    {
        $images = [];

        $cover = $product->cover->first();
        if ($cover?->file_path) {
            $images[] = asset('storage/' . $cover->file_path);
        }

        foreach ($product->gallery as $media) {
            if ($media->file_path) {
                $images[] = asset('storage/' . $media->file_path);
            }
        }

        return array_values(array_unique($images));
    }

    private function maxUpdated(Collection $items): ?Carbon
    {
        $max = $items->max('updated_at');

        return $max ? Carbon::parse($max) : null;
    }

    private function iso(\DateTimeInterface|string|null $value): string
    {
        if ($value === null) {
            return Carbon::now()->toAtomString();
        }

        return Carbon::parse($value)->toAtomString();
    }

    private function cacheKey(string $segment, Partner $partner): string
    {
        return sprintf('partner_sitemap:%d:%s', $partner->id, $segment);
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
