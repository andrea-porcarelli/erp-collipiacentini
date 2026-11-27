<?php

use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

// Route pubbliche per il widget embeddabile (letture + rilascio token).
// CORS aperto: il widget è caricato da siti WordPress dei partner.
Route::prefix('v1/public')->middleware('widget.cors')->group(function () {
    Route::get('/products/{id}', [\App\Http\Controllers\Api\V1\PublicProductController::class, 'show']);
    Route::get('/products/{id}/availability', [AvailabilityController::class, 'index']);
    Route::post('/products/{id}/availability/check', [AvailabilityController::class, 'check']);
    Route::post('/widget/token', [\App\Http\Controllers\Api\V1\WidgetAuthController::class, 'issueToken']);
});

// Route widget autenticate con token short-lived.
Route::prefix('v1/widget')->middleware(['widget.cors', 'api.widget_token'])->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/customer', [OrderController::class, 'customer']);
    Route::post('/orders/{id}/payment-intent', [OrderController::class, 'paymentIntent']);
    Route::post('/orders/{id}/confirm', [OrderController::class, 'confirm']);
});

Route::prefix('v1')->middleware('api.token')->group(function () {
    Route::get('/health', [HealthController::class, 'index']);

    Route::get('/catalog', [CatalogController::class, 'index']);
    Route::get('/catalog/info', [CatalogController::class, 'info']);
    Route::get('/catalog/available', [CatalogController::class, 'available']);

    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products/batch', [ProductController::class, 'batch']);

    Route::get('/products/{id}/availability', [AvailabilityController::class, 'index']);
    Route::post('/products/{id}/availability/check', [AvailabilityController::class, 'check']);

    Route::post('/sync/trigger', [SyncController::class, 'trigger']);
});
