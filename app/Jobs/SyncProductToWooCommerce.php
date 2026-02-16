<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncProductToWooCommerce implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public Product $product,
        public Company $company
    ) {}

    public function handle(): void
    {
        $endpoint = $this->company->endpoint_woocommerce;

        if (!$endpoint) {
            Log::warning("SyncProductToWooCommerce: endpoint mancante per company {$this->company->id}");
            return;
        }

        try {
            $response = Http::withHeaders([
                'token' => $this->company->token,
            ])->post($endpoint, [
                'product_id' => $this->product->id,
                'label' => $this->product->label,
                'product_code' => $this->product->product_code,
                'is_active' => $this->product->is_active,
                'duration' => $this->product->duration,
                'category' => $this->product->category?->label,
                'prices' => $this->product->prices->map(function ($price) {
                    return [
                        'id' => $price->id,
                        'price' => $price->price,
                    ];
                })->toArray(),
            ]);

            if ($response->failed()) {
                Log::error("SyncProductToWooCommerce: errore sincronizzazione prodotto {$this->product->id} - Status: {$response->status()} - Body: {$response->body()}");
            } else {
                Log::info("SyncProductToWooCommerce: prodotto {$this->product->id} sincronizzato con successo per company {$this->company->id}");
            }
        } catch (\Exception $e) {
            Log::error("SyncProductToWooCommerce: eccezione per prodotto {$this->product->id} - {$e->getMessage()}");
            throw $e;
        }
    }
}
