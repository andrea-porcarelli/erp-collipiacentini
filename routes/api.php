<?php

use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.token')->group(function () {
    Route::get('/health', [HealthController::class, 'index']);

    Route::get('/catalog', [CatalogController::class, 'index']);
    Route::get('/catalog/info', [CatalogController::class, 'info']);

    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products/batch', [ProductController::class, 'batch']);

    Route::get('/products/{id}/availability', [AvailabilityController::class, 'index']);
    Route::post('/products/{id}/availability/check', [AvailabilityController::class, 'check']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
});
