<?php

declare(strict_types=1);

use App\Cart\CartController;
use App\Catalog\ProductsController;
use App\Checkout\CheckoutController;
use App\Middleware\RateLimiter;
use Yiisoft\Router\Route;

return [
    // Product catalog — proxied live from invoice's GET /api/products,
    // no local storage. Deliberately minimal for v1: flat list + detail,
    // no search/categories/filtering (see docs/DESIGN.md).
    Route::get('/')
        ->name('catalog/index')
        ->action([ProductsController::class, 'index']),

    Route::get('/products/{id}')
        ->name('catalog/show')
        ->action([ProductsController::class, 'show']),

    // Session cart — no database row of its own.
    Route::get('/cart')
        ->name('cart/index')
        ->action([CartController::class, 'index']),

    Route::post('/cart/add')
        ->name('cart/add')
        ->action([CartController::class, 'add']),

    Route::post('/cart/update')
        ->name('cart/update')
        ->action([CartController::class, 'update']),

    Route::post('/cart/remove/{id}')
        ->name('cart/remove')
        ->action([CartController::class, 'remove']),

    // The actual point of this app — see CheckoutController::submit()
    // and OrderApiClient for the handoff to invoice's own checkout.
    Route::get('/checkout')
        ->name('checkout/index')
        ->action([CheckoutController::class, 'index']),

    Route::post('/checkout')
        ->middleware(RateLimiter::perIp(10, 'checkout_submit_route'))
        ->name('checkout/submit')
        ->action([CheckoutController::class, 'submit']),
];
