<?php

namespace App\Providers;

use App\Interfaces\CalendarInterface;
use App\Interfaces\CategoryInterface;
use App\Interfaces\CompanyInterface;
use App\Interfaces\OrderInterface;
use App\Interfaces\PartnerConsentInterface;
use App\Interfaces\PartnerInterface;
use App\Interfaces\ProductCustomerFieldInterface;
// namespace here

use App\Interfaces\ProductFaqInterface;
use App\Interfaces\ProductInterface;
use App\Interfaces\ProductLinkInterface;
use App\Interfaces\ProductRelatedInterface;
use App\Interfaces\UserInterface;
use App\Repositories\CalendarRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PartnerConsentRepository;
use App\Repositories\PartnerRepository;
use App\Repositories\ProductCustomerFieldRepository;
use App\Repositories\ProductFaqRepository;
use App\Repositories\ProductLinkRepository;
use App\Repositories\ProductRelatedRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // register here
        $this->app->bind(PartnerInterface::class, PartnerRepository::class);
        $this->app->bind(CompanyInterface::class, CompanyRepository::class);
        $this->app->bind(CategoryInterface::class, CategoryRepository::class);
        $this->app->bind(ProductInterface::class, ProductRepository::class);
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(OrderInterface::class, OrderRepository::class);
        $this->app->bind(ProductFaqInterface::class, ProductFaqRepository::class);
        $this->app->bind(ProductLinkInterface::class, ProductLinkRepository::class);
        $this->app->bind(ProductRelatedInterface::class, ProductRelatedRepository::class);
        $this->app->bind(ProductCustomerFieldInterface::class, ProductCustomerFieldRepository::class);
        $this->app->bind(PartnerConsentInterface::class, PartnerConsentRepository::class);
        $this->app->bind(CalendarInterface::class, CalendarRepository::class);

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
