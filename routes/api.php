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
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\TravelPackageController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\TransportTicketController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Public Routes (Tanpa Auth)
|--------------------------------------------------------------------------
*/

// Auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Destinations
Route::get('/destination-categories', [DestinationController::class, 'categories']);
Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{slug}', [DestinationController::class, 'show']);

// UMKM
Route::get('/destinations/{destinationSlug}/umkms', [UmkmController::class, 'byDestination']);
Route::get('/umkms/{slug}', [UmkmController::class, 'show']);
Route::get('/umkms/{umkmSlug}/products', [ProductController::class, 'byUmkm']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// Events
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event:slug}', [EventController::class, 'show']);

// Reviews (publik bisa baca)
Route::get('/reviews', [ReviewController::class, 'index']);

// ===== FITUR BARU (PUBLIC) =====

// Hotels
Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/hotels/{slug}', [HotelController::class, 'show']);

// Transportations
Route::prefix('transport-tickets')->group(function () {
    Route::get('/search', [TransportTicketController::class, 'search']);
    Route::get('/{id}', [TransportTicketController::class, 'show']);
});

// Travel Packages
Route::get('/travel-packages', [TravelPackageController::class, 'index']);
Route::get('/travel-packages/{slug}', [TravelPackageController::class, 'show']);


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

    // Orders (UMKM)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    // ===== BOOKING BARU =====
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{bookingNumber}', [BookingController::class, 'show']);
    Route::post('/bookings/{bookingNumber}/cancel', [BookingController::class, 'cancel']);

    // Wallet & Coin
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet/top-up-history', [WalletController::class, 'topUpHistory']);
    Route::post('/wallet/top-up', [WalletController::class, 'requestTopUp']);
    Route::get('/wallet/check-status/{orderId}', [WalletController::class, 'checkTopUpStatus']);
    Route::post('/wallet/simulate-webhook/{orderId}', [WalletController::class, 'simulateWebhook']);

    // Webhook Midtrans
    Route::post('/midtrans/notification', [WalletController::class, 'handleMidtransNotification']);

    // Wishlist
    Route::get('/wishlists', [WishlistController::class, 'index']);
    Route::post('/wishlists', [WishlistController::class, 'store']);
    Route::delete('/wishlists/{wishlist}', [WishlistController::class, 'destroy']);
    Route::get('/wishlists/check/{destination}', [WishlistController::class, 'check']);

    // Reviews
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