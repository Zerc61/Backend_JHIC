<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\UmkmController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TripPlanController;
use App\Http\Controllers\Api\Dashboard\DashboardUmkmController;
use App\Http\Controllers\Api\Dashboard\DashboardAdminController;
use Illuminate\Support\Facades\Route;


Route::post('/midtrans/notification', [WalletController::class, 'handleMidtransNotification']);

// Simulasi Webhook (TANPA AUTH - untuk testing saja, hapus di production)
Route::get('/simulasi-webhook-midtrans/{orderId}', [WalletController::class, 'simulateWebhook']);

/*
|--------------------------------------------------------------------------
| Public Routes (Tanpa Auth)
|--------------------------------------------------------------------------
*/

// Auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Destinations
// Destinations
Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destination-categories', [DestinationController::class, 'categories']); // <-- DIPINDAH & DIUBAH NAMANYA
Route::get('/destinations/{slug}', [DestinationController::class, 'show']);

// UMKM by Destination
Route::get('/destinations/{destinationSlug}/umkms', [UmkmController::class, 'byDestination']);

// Products by UMKM
Route::get('/umkms/{umkmSlug}/products', [ProductController::class, 'byUmkm']);

// UMKM Detail
Route::get('/umkms/{slug}', [UmkmController::class, 'show']);

// Product Detail
Route::get('/products/{slug}', [ProductController::class, 'show']);

// Events
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{slug}', [EventController::class, 'show']);

// Reviews (publik bisa baca)
Route::get('/reviews', [ReviewController::class, 'index']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Butuh Auth)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Wallet & Coin
 Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet/top-up/history', [WalletController::class, 'topUpHistory']);
    Route::post('/wallet/top-up', [WalletController::class, 'requestTopUp']);
    Route::get('/wallet/top-up/check/{orderId}', [WalletController::class, 'checkTopUpStatus']);

    // Wishlist
    Route::get('/wishlists', [WishlistController::class, 'index']);
    Route::post('/wishlists', [WishlistController::class, 'store']);
    Route::delete('/wishlists/{destination}', [WishlistController::class, 'destroy']);
    Route::get('/wishlists/check/{destination}', [WishlistController::class, 'check']);

    // Reviews (tulis butuh auth)
    Route::post('/reviews', [ReviewController::class, 'store']);

// Trip Plans
Route::get('/trip-plans', [TripPlanController::class, 'index']);
Route::get('/trip-plans/destinations', [TripPlanController::class, 'availableDestinations']);
Route::post('/trip-plans', [TripPlanController::class, 'store']);
Route::get('/trip-plans/{tripPlan}', [TripPlanController::class, 'show']);
Route::delete('/trip-plans/{tripPlan}', [TripPlanController::class, 'destroy']);

    // Dashboard UMKM
    Route::prefix('dashboard/umkm')->group(function () {
        Route::get('/stats', [DashboardUmkmController::class, 'stats']);
        Route::get('/products', [DashboardUmkmController::class, 'products']);
        Route::get('/orders', [DashboardUmkmController::class, 'orders']);
    });

    // Dashboard Admin
    Route::prefix('dashboard/admin')->group(function () {
        Route::get('/stats', [DashboardAdminController::class, 'stats']);
    });
});