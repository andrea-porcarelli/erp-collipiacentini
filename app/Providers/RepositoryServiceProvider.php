<?php

namespace App\Providers;

use App\Interfaces\OrderInterface;
use App\Interfaces\ProductInterface;
use App\Interfaces\UserInterface;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

#namespace here

use App\Interfaces\CustomerInterface;
use App\Repositories\CustomerRepository;

use App\Interfaces\PartnerInterface;
use App\Repositories\PartnerRepository;

use App\Interfaces\CompanyInterface;
use App\Repositories\CompanyRepository;

use App\Interfaces\CategoryInterface;
use App\Repositories\CategoryRepository;

use App\Interfaces\ProductFaqInterface;
use App\Repositories\ProductFaqRepository;

use App\Interfaces\ProductLinkInterface;
use App\Repositories\ProductLinkRepository;

use App\Interfaces\ProductRelatedInterface;
use App\Repositories\ProductRelatedRepository;

use App\Interfaces\ProductCustomerFieldInterface;
use App\Repositories\ProductCustomerFieldRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register() : void
    {
        #register here
        $this->app->bind(CustomerInterface::class, CustomerRepository::class);
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
