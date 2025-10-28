<?php

use App\Http\Controllers\Backoffice\BookingController;
use App\Http\Controllers\Backoffice\CategoryController;
use App\Http\Controllers\Backoffice\CompanyController;
use App\Http\Controllers\Backoffice\CustomerController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\LoginController;
use App\Http\Controllers\Backoffice\OrderController;
use App\Http\Controllers\Backoffice\PartnerController;
use App\Http\Controllers\Backoffice\ProductController;
use App\Http\Controllers\Backoffice\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::group(['prefix' => '/booking', 'middleware' => ['token']], function() {
    Route::get('/',[BookingController::class, 'index']);
});
Route::group(['prefix' => '/backoffice'], function() {
    Route::get('/login',[LoginController::class, 'index'])->name('login');
    Route::post('/login',[LoginController::class, 'login']);

    Route::group(['middleware' => ['auth']], function() {
        Route::impersonate();
        Route::get('/index', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('orders', OrderController::class);
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('partners', PartnerController::class);
        Route::resource('companies', CompanyController::class);
        Route::resource('users', UserController::class);
        Route::resource('customers', CustomerController::class);
    });
});
