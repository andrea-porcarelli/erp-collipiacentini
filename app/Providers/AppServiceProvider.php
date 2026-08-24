<?php

namespace App\Providers;

use App\Contracts\InvoiceProvider;
use App\Facades\ResourceRegistrar;
use App\Services\Invoicing\FattureInCloudProvider;
use App\Services\Invoicing\NullInvoiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InvoiceProvider::class, function ($app) {
            $config = $app['config']->get('services.fatture_in_cloud', []);
            $provider = new FattureInCloudProvider(
                accessToken: $config['access_token'] ?? null,
                companyId: $config['company_id'] ? (int) $config['company_id'] : null,
                apiBase: $config['api_base'] ?? 'https://api-v2.fattureincloud.it',
            );

            return $provider->isConfigured() ? $provider : new NullInvoiceProvider;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        $registrar = new ResourceRegistrar($this->app['router']);

        $this->app->bind('Illuminate\Routing\ResourceRegistrar', function () use ($registrar) {
            return $registrar;
        });
    }
}
