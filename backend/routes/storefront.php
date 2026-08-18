<?php

use App\Http\Controllers\Api\StorefrontApiController;
use Illuminate\Support\Facades\Route;

/*
 * Headless storefront REST API consumed by the React frontend.
 * Mounted under /api (Laravel's default api prefix).
 *
 *   GET   /api/v1/storefront/bootstrap
 *   GET   /api/v1/storefront/products
 *   GET   /api/v1/storefront/products/{idOrSlug}
 *   GET   /api/v1/storefront/search?q=
 *   GET   /api/v1/storefront/blogs
 *   GET   /api/v1/storefront/blogs/{slug}
 *   POST  /api/v1/storefront/coupons/apply
 *   POST  /api/v1/storefront/orders
 *   GET   /api/v1/storefront/orders/track/{invoice}
 *   POST  /api/v1/storefront/reviews
 *   POST  /api/v1/storefront/complaints
 *   POST  /api/v1/storefront/contact
 */

Route::prefix('v1/storefront')->group(function () {
    Route::get('bootstrap', [StorefrontApiController::class, 'bootstrap']);

    Route::get('products', [StorefrontApiController::class, 'products']);
    Route::get('products/{idOrSlug}', [StorefrontApiController::class, 'product']);

    Route::get('search', [StorefrontApiController::class, 'search']);

    Route::get('blogs', [StorefrontApiController::class, 'blogs']);
    Route::get('blogs/{slug}', [StorefrontApiController::class, 'blog']);

    Route::post('coupons/apply', [StorefrontApiController::class, 'applyCoupon']);

    Route::post('orders', [StorefrontApiController::class, 'createOrder']);
    Route::get('orders/track/{invoice}', [StorefrontApiController::class, 'trackOrder']);

    // Customer authentication (Sanctum bearer tokens)
    Route::post('auth/register', [StorefrontApiController::class, 'register']);
    Route::post('auth/login', [StorefrontApiController::class, 'login']);
    Route::post('auth/logout', [StorefrontApiController::class, 'logout']);
    Route::get('auth/me', [StorefrontApiController::class, 'me']);
    Route::get('auth/orders', [StorefrontApiController::class, 'myOrders']);

    Route::post('reviews', [StorefrontApiController::class, 'submitReview']);
    Route::post('complaints', [StorefrontApiController::class, 'submitComplaint']);
    Route::post('contact', [StorefrontApiController::class, 'submitContact']);
});
