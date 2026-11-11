<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public storefront
|--------------------------------------------------------------------------
*/

Route::get('/settings', [SettingController::class, 'index']);

Route::get('/pages', [PageController::class, 'index']);
Route::get('/pages/{page}', [PageController::class, 'show']);

// NOTE: literal segments must be registered before /products/{product},
// otherwise "categories" and "search" get swallowed by the wildcard.
Route::get('/products/categories', [CategoryController::class, 'index']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/category/{category}', [CategoryController::class, 'products']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/related', [ProductController::class, 'related']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);

Route::post('/cart/validate', [CartController::class, 'validateCart']);

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Orders - guest checkout allowed, tracking is phone-verified
|--------------------------------------------------------------------------
*/

Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['optional.auth', 'throttle:20,1']);

Route::post('/orders/track', [OrderController::class, 'track'])
    ->middleware('throttle:20,1');

Route::get('/orders/{orderNumber}/invoice', [OrderController::class, 'invoice'])
    ->name('api.orders.invoice')
    ->middleware('throttle:60,1');

Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])
    ->middleware(['optional.auth', 'throttle:10,1']);

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:15,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('/phone', [AuthController::class, 'phoneLogin'])->middleware('throttle:20,1');
    Route::post('/google', [AuthController::class, 'googleLogin'])->middleware('throttle:20,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated customer area
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
    Route::post('/wishlist/sync', [WishlistController::class, 'sync']);
    Route::delete('/wishlist', [WishlistController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::post('/products/images', [AdminProductController::class, 'uploadImage']);
    Route::apiResource('products', AdminProductController::class);
    Route::apiResource('categories', AdminCategoryController::class)->except(['show']);

    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
});
