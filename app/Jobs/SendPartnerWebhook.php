<?php

namespace App\Jobs;

use App\Models\Partner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPartnerWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $backoff = 30;

    public function __construct(
        public int $partnerId,
        public string $event,
        public array $payload,
        public string $path = '/wp-json/miticko/v1/sync',
    ) {}

    public function handle(): void
    {
        $partner = Partner::find($this->partnerId);

        if (! $partner) {
            Log::warning("SendPartnerWebhook: partner {$this->partnerId} non trovato");

            return;
        }

        if (! $partner->wordpress_endpoint || ! $partner->wordpress_webhook_secret) {
            Log::info("SendPartnerWebhook: partner {$partner->id} senza endpoint o secret, salto");

            return;
        }

        $url = rtrim($partner->wordpress_endpoint, '/').$this->path;

        $body = [
            'event' => $this->event,
            'sent_at' => now()->toIso8601String(),
            'partner_id' => $partner->id,
            'data' => $this->payload,
        ];

        $json = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $json, $partner->wordpress_webhook_secret);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Miticko-Event' => $this->event,
                'X-Miticko-Signature' => $signature,
            ])->withBody($json, 'application/json')->post($url);

            if ($response->failed()) {
                Log::error("SendPartnerWebhook: partner {$partner->id} event {$this->event} - HTTP {$response->status()} - {$response->body()}");
                $this->release($this->backoff);
            }
        } catch (\Throwable $e) {
            Log::error("SendPartnerWebhook: partner {$partner->id} event {$this->event} - {$e->getMessage()}");
            throw $e;
        }
    }
}
