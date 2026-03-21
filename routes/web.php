<?php

use App\Http\Controllers\Frontend\BookingController;
use App\Http\Controllers\Frontend\PaymentController;
use App\Http\Controllers\Frontend\StripeWebhookController;
use App\Http\Controllers\Backoffice\CategoryController;
use App\Http\Controllers\Backoffice\CompanyController;
use App\Http\Controllers\Backoffice\CustomerController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\LoginController;
use App\Http\Controllers\Backoffice\OrderController;
use App\Http\Controllers\Backoffice\PartnerController;
use App\Http\Controllers\Backoffice\ProductController;
use App\Http\Controllers\Backoffice\ProductFaqController;
use App\Http\Controllers\Backoffice\ProductLinkController;
use App\Http\Controllers\Backoffice\ProductRelatedController;
use App\Http\Controllers\Backoffice\ProductAvailabilityController;
use App\Http\Controllers\Backoffice\ProductPriceVariationController;
use App\Http\Controllers\Backoffice\ProductCustomerFieldController;
use App\Http\Controllers\Backoffice\PartnerUserController;
use App\Http\Controllers\Backoffice\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/shop'], function() {
    Route::get('/',[BookingController::class, 'index'])->middleware('token');
    Route::get('/filter-products',[BookingController::class, 'filterProducts'])->middleware('token');
    Route::get('/product/{productId}/available-times',[BookingController::class, 'getAvailableTimes']);
    Route::post('/cart/add',[BookingController::class, 'addToCart'])->name('booking.cart.add');
    Route::delete('/cart/remove',[BookingController::class, 'removeCart'])->name('booking.cart.remove');
    Route::post('/cart/customer',[BookingController::class, 'saveCustomer'])->name('booking.cart.customer');
    Route::get('/cart',[BookingController::class, 'cart'])->name('booking.cart');
    Route::get('/{slugPartner}/{slugProduct}-{productCode}.html',[BookingController::class, 'product'])->name('booking.product');

    // Payment routes
    Route::post('/payment/create-intent', [PaymentController::class, 'createIntent'])->name('payment.create-intent');
    Route::post('/payment/confirm', [PaymentController::class, 'confirm'])->name('payment.confirm');
    Route::get('/order/success/{orderNumber}', [PaymentController::class, 'success_payment'])->name('order.success');
});

// Stripe Webhook (fuori dal middleware CSRF)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');
Route::domain('admin.miticko.com')->group(function () {

    Route::get('/', function () {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        } else {
            return redirect()->route('login');
        }
    });
    Route::get('/login',[LoginController::class, 'index'])->name('login');
    Route::post('/login',[LoginController::class, 'login']);

    Route::group(['middleware' => ['auth']], function() {
        Route::impersonate();
        Route::get('/index', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('orders', OrderController::class);
        Route::resource('products', ProductController::class);
        Route::get('products/{product}/price-variations', [ProductPriceVariationController::class, 'index'])->name('products.price-variations.index');
        Route::post('products/{product}/price-variations', [ProductPriceVariationController::class, 'store'])->name('products.price-variations.store');
        Route::put('products/{product}/price-variations/{variation}', [ProductPriceVariationController::class, 'update'])->name('products.price-variations.update');
        Route::delete('products/{product}/price-variations/{variation}', [ProductPriceVariationController::class, 'destroy'])->name('products.price-variations.destroy');
        Route::post('products/{product}/sync-woocommerce', [ProductController::class, 'syncWooCommerce'])->name('products.sync-woocommerce');
        Route::post('products/{product}/variants/reorder', [ProductController::class, 'reorderVariants'])->name('products.variants.reorder');
        Route::post('products/{product}/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
        Route::put('products/{product}/variants/{variant}', [ProductController::class, 'updateVariant'])->name('products.variants.update');
        Route::delete('products/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');
        Route::get('products/{product}/variants/{variant}/translations', [ProductController::class, 'getVariantTranslations'])->name('products.variants.translations.get');
        Route::put('products/{product}/variants/{variant}/translations', [ProductController::class, 'saveVariantTranslations'])->name('products.variants.translations.save');
        Route::get('products/{product}/links', [ProductLinkController::class, 'index'])->name('products.links.index');
        Route::post('products/{product}/links', [ProductLinkController::class, 'store'])->name('products.links.store');
        Route::put('products/{product}/links/{link}', [ProductLinkController::class, 'update'])->name('products.links.update');
        Route::delete('products/{product}/links/{link}', [ProductLinkController::class, 'destroy'])->name('products.links.destroy');
        Route::get('products/{product}/links/{link}/translations', [ProductLinkController::class, 'getTranslations'])->name('products.links.translations.get');
        Route::put('products/{product}/links/{link}/translations', [ProductLinkController::class, 'saveTranslations'])->name('products.links.translations.save');
        Route::get('products/{product}/faqs', [ProductFaqController::class, 'index'])->name('products.faqs.index');
        Route::post('products/{product}/faqs', [ProductFaqController::class, 'store'])->name('products.faqs.store');
        Route::put('products/{product}/faqs/{faq}', [ProductFaqController::class, 'update'])->name('products.faqs.update');
        Route::delete('products/{product}/faqs/{faq}', [ProductFaqController::class, 'destroy'])->name('products.faqs.destroy');
        Route::get('products/{product}/faqs/{faq}/translations', [ProductFaqController::class, 'getTranslations'])->name('products.faqs.translations.get');
        Route::put('products/{product}/faqs/{faq}/translations', [ProductFaqController::class, 'saveTranslations'])->name('products.faqs.translations.save');
        Route::get('products/{product}/related/search', [ProductRelatedController::class, 'find'])->name('products.related.search');
        Route::get('products/{product}/related', [ProductRelatedController::class, 'index'])->name('products.related.index');
        Route::post('products/{product}/related', [ProductRelatedController::class, 'store'])->name('products.related.store');
        Route::put('products/{product}/related', [ProductRelatedController::class, 'sync'])->name('products.related.sync');
        Route::delete('products/{product}/related/{related}', [ProductRelatedController::class, 'destroy'])->name('products.related.destroy');
        Route::post('products/{product}/customer-fields/sync', [ProductCustomerFieldController::class, 'sync'])->name('products.customer-fields.sync');
        Route::get('products/{product}/schedule/{dayIndex}', [ProductAvailabilityController::class, 'index'])->name('products.schedule.index');
        Route::post('products/{product}/schedule', [ProductAvailabilityController::class, 'store'])->name('products.schedule.store');
        Route::put('products/{product}/schedule/{slot}', [ProductAvailabilityController::class, 'update'])->name('products.schedule.update');
        Route::delete('products/{product}/schedule/{slot}', [ProductAvailabilityController::class, 'destroy'])->name('products.schedule.destroy');
        Route::resource('categories', CategoryController::class);
        Route::resource('partners', PartnerController::class);
        Route::post('partners/{partner}/users', [PartnerUserController::class, 'store'])->name('partners.users.store');
        Route::put('partners/{partner}/users/{user}', [PartnerUserController::class, 'update'])->name('partners.users.update');
        Route::delete('partners/{partner}/users/{user}', [PartnerUserController::class, 'destroy'])->name('partners.users.destroy');
        Route::resource('companies', CompanyController::class);
        Route::post('companies/{company}/generate-token', [CompanyController::class, 'generateToken'])->name('companies.generate-token');
        Route::put('companies/{company}/products', [CompanyController::class, 'syncProducts'])->name('companies.products.sync');
        Route::resource('users', UserController::class);
        Route::resource('customers', CustomerController::class);
    });
});
//Route::group(['prefix' => '/backoffice'], function() {
//    Route::get('/login',[LoginController::class, 'index'])->name('login');
//    Route::post('/login',[LoginController::class, 'login']);
//
//    Route::group(['middleware' => ['auth']], function() {
//        Route::impersonate();
//        Route::get('/index', [DashboardController::class, 'index'])->name('dashboard');
//
//        Route::resource('orders', OrderController::class);
//        Route::resource('products', ProductController::class);
//        Route::get('products/partners-by-company/{companyId}', [ProductController::class, 'partnersByCompany'])->name('products.partners-by-company');
//        Route::post('products/{product}/sync-woocommerce', [ProductController::class, 'syncWooCommerce'])->name('products.sync-woocommerce');
//        Route::post('products/{product}/variants/reorder', [ProductController::class, 'reorderVariants'])->name('products.variants.reorder');
//        Route::post('products/{product}/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
//        Route::put('products/{product}/variants/{variant}', [ProductController::class, 'updateVariant'])->name('products.variants.update');
//        Route::delete('products/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');
//        Route::get('products/{product}/links', [ProductLinkController::class, 'index'])->name('products.links.index');
//        Route::post('products/{product}/links', [ProductLinkController::class, 'store'])->name('products.links.store');
//        Route::put('products/{product}/links/{link}', [ProductLinkController::class, 'update'])->name('products.links.update');
//        Route::delete('products/{product}/links/{link}', [ProductLinkController::class, 'destroy'])->name('products.links.destroy');
//        Route::get('products/{product}/links/{link}/translations', [ProductLinkController::class, 'getTranslations'])->name('products.links.translations.get');
//        Route::put('products/{product}/links/{link}/translations', [ProductLinkController::class, 'saveTranslations'])->name('products.links.translations.save');
//        Route::get('products/{product}/faqs', [ProductFaqController::class, 'index'])->name('products.faqs.index');
//        Route::post('products/{product}/faqs', [ProductFaqController::class, 'store'])->name('products.faqs.store');
//        Route::put('products/{product}/faqs/{faq}', [ProductFaqController::class, 'update'])->name('products.faqs.update');
//        Route::delete('products/{product}/faqs/{faq}', [ProductFaqController::class, 'destroy'])->name('products.faqs.destroy');
//        Route::get('products/{product}/faqs/{faq}/translations', [ProductFaqController::class, 'getTranslations'])->name('products.faqs.translations.get');
//        Route::put('products/{product}/faqs/{faq}/translations', [ProductFaqController::class, 'saveTranslations'])->name('products.faqs.translations.save');
//        Route::get('products/{product}/related/search', [ProductRelatedController::class, 'find'])->name('products.related.search');
//        Route::get('products/{product}/related', [ProductRelatedController::class, 'index'])->name('products.related.index');
//        Route::post('products/{product}/related', [ProductRelatedController::class, 'store'])->name('products.related.store');
//        Route::put('products/{product}/related', [ProductRelatedController::class, 'sync'])->name('products.related.sync');
//        Route::delete('products/{product}/related/{related}', [ProductRelatedController::class, 'destroy'])->name('products.related.destroy');
//        Route::post('products/{product}/customer-fields/sync', [ProductCustomerFieldController::class, 'sync'])->name('products.customer-fields.sync');
//        Route::resource('categories', CategoryController::class);
//        Route::resource('partners', PartnerController::class);
//        Route::post('partners/{partner}/users', [PartnerUserController::class, 'store'])->name('partners.users.store');
//        Route::put('partners/{partner}/users/{user}', [PartnerUserController::class, 'update'])->name('partners.users.update');
//        Route::delete('partners/{partner}/users/{user}', [PartnerUserController::class, 'destroy'])->name('partners.users.destroy');
//        Route::resource('companies', CompanyController::class);
//        Route::post('companies/{company}/generate-token', [CompanyController::class, 'generateToken'])->name('companies.generate-token');
//        Route::resource('users', UserController::class);
//        Route::resource('customers', CustomerController::class);
//    });
//});
