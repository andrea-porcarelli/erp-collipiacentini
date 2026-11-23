<?php

use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.token')->group(function () {
    Route::get('/health', [HealthController::class, 'index']);

    Route::get('/catalog', [CatalogController::class, 'index']);
    Route::get('/catalog/info', [CatalogController::class, 'info']);

    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products/batch', [ProductController::class, 'batch']);

    Route::get('/products/{id}/availability', [AvailabilityController::class, 'index']);
    Route::post('/products/{id}/availability/check', [AvailabilityController::class, 'check']);

    Route::post('/sync/trigger', [SyncController::class, 'trigger']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/customer', [OrderController::class, 'customer']);
    Route::post('/orders/{id}/payment-intent', [OrderController::class, 'paymentIntent']);
    Route::post('/orders/{id}/confirm', [OrderController::class, 'confirm']);
});
