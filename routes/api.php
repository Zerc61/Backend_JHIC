<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\UmkmController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AvailabilityController;
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
use App\Http\Controllers\Api\TransportationController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminDestinationController;
use App\Http\Controllers\Api\Admin\AdminHotelController;
use App\Http\Controllers\Api\Admin\AdminHotelRoomController;
use App\Http\Controllers\Api\Admin\AdminTravelPackageController;
use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminTransportTicketController;
use App\Http\Controllers\Api\Admin\AdminUmkmController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminBookingController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminFacilityController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminReviewController;
use App\Http\Controllers\Api\Manager\ManagerDashboardController;
use App\Http\Controllers\Api\Manager\ManagerDestinationController;
use App\Http\Controllers\Api\Manager\ManagerHotelController;
use App\Http\Controllers\Api\Manager\ManagerHotelRoomController;
use App\Http\Controllers\Api\Manager\ManagerGalleryController;
use App\Http\Controllers\Api\Manager\ManagerTravelPackageController;
use App\Http\Controllers\Api\Manager\ManagerTransportationController;
use App\Http\Controllers\Api\Manager\ManagerEventController;
use App\Http\Controllers\Api\Manager\ManagerBookingController;
use App\Http\Controllers\Api\Umkm\UmkmDashboardController;
use App\Http\Controllers\Api\Umkm\UmkmProfileController;
use App\Http\Controllers\Api\Umkm\UmkmProductController;
use App\Http\Controllers\Api\Umkm\UmkmProductImageController;
use App\Http\Controllers\Api\Umkm\UmkmOrderController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Public Routes (Tanpa Auth)
|--------------------------------------------------------------------------
*/

// Auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/dashboard', [App\Http\Controllers\Api\Dashboard\DashboardUserController::class, 'index']);
// Fix for BUG 6: named login route to avoid 500
Route::get('/login', fn () => response()->json(['message' => 'Unauthorized'], 401))->name('login');

// Destinations
Route::get('/destination-categories', [DestinationController::class, 'categories']);
Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{slug}', [DestinationController::class, 'show']);

// UMKM
Route::get('/umkm-categories', [UmkmController::class, 'categories']);
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
    Route::get('/stats', [TransportTicketController::class, 'stats']);
    Route::get('/{id}', [TransportTicketController::class, 'show']);
});
Route::get('/transport-tickets', [TransportTicketController::class, 'index']);

// Travel Packages
Route::get('/travel-packages', [TravelPackageController::class, 'index']);
Route::get('/travel-packages/{slug}', [TravelPackageController::class, 'show']);

// Transportations (Rental Kendaraan)
Route::get('/transportations', [TransportationController::class, 'index']);
Route::get('/transportations/{slug}', [TransportationController::class, 'show']);

// Vouchers (Public list)
Route::get('/vouchers', [VoucherController::class, 'list']);

// Webhook Midtrans (Public, but with signature verification)
Route::post('/midtrans/notification', [WalletController::class, 'handleMidtransNotification']);

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

    // Wishlist
   Route::get('/wishlists', [WishlistController::class, 'index']);
    Route::post('/wishlists', [WishlistController::class, 'store']);
    Route::get('/wishlists/check/{destinationId}', [WishlistController::class, 'check']);
    Route::delete('/wishlists/{destinationId}', [WishlistController::class, 'destroy']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Trip Plans
    Route::get('/trip-plans', [TripPlanController::class, 'index']);
    Route::get('/trip-plans/destinations', [TripPlanController::class, 'availableDestinations']);
    Route::post('/trip-plans', [TripPlanController::class, 'store']);
    Route::get('/trip-plans/{tripPlan}', [TripPlanController::class, 'show']);
    Route::delete('/trip-plans/{tripPlan}', [TripPlanController::class, 'destroy']);

     Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('{bookingNumber}', [BookingController::class, 'show']);
        Route::post('{bookingNumber}/cancel', [BookingController::class, 'cancel']);
    });

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/{notification}/unread', [NotificationController::class, 'markAsUnread']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'delete']);
    Route::delete('/notifications', [NotificationController::class, 'deleteAll']);

    // Refunds
    Route::get('/refunds', [RefundController::class, 'index']);
    Route::get('/refunds/{refund}', [RefundController::class, 'show']);
    Route::post('/orders/{order}/request-refund', [RefundController::class, 'requestOrderRefund']);
    Route::post('/bookings/{booking}/request-refund', [RefundController::class, 'requestBookingRefund']);

    // Vouchers
    Route::post('/vouchers/validate', [VoucherController::class, 'validate']);
    Route::post('/orders/{order}/apply-voucher', [VoucherController::class, 'applyToOrder']);
    Route::post('/bookings/{booking}/apply-voucher', [VoucherController::class, 'applyToBooking']);

    // Payments (Direct gateway)
    Route::post('/orders/{order}/initiate-payment', [PaymentController::class, 'initiateOrderPayment']);
    Route::post('/bookings/{booking}/initiate-payment', [PaymentController::class, 'initiateBookingPayment']);
    Route::get('/payments/{payment}/status', [PaymentController::class, 'checkPaymentStatus']);
    Route::get('/payments/history', [PaymentController::class, 'getPaymentHistory']);

    // Booking Holds & Availability
    Route::get('/hotel-rooms/{roomId}/availability', [AvailabilityController::class, 'hotelRoomAvailability']);
    Route::get('/transportations/{transportationId}/availability', [AvailabilityController::class, 'transportationAvailability']);
    Route::get('/packages/{packageId}/availability', [AvailabilityController::class, 'packageAvailability']);

});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Users
    Route::apiResource('users', AdminUserController::class);

    // Kategori Destination
    Route::apiResource('destination-categories', AdminCategoryController::class);

    // Fasilitas
    Route::apiResource('facilities', AdminFacilityController::class);

  // Destinations
    Route::apiResource('destinations', AdminDestinationController::class);
    Route::put('destinations/{destination}/assign-manager', [AdminDestinationController::class, 'assignManager']);
    Route::post('destinations/{destination}/galleries', [AdminDestinationController::class, 'storeGallery']);
    Route::delete('destinations/{destination}/galleries/{gallery}', [AdminDestinationController::class, 'destroyGallery']);

    // Hotels
    Route::apiResource('hotels', AdminHotelController::class);
    Route::put('hotels/{hotel}/assign-manager', [AdminHotelController::class, 'assignManager']);
    Route::post('hotels/{hotel}/galleries', [AdminHotelController::class, 'storeGallery']);
    Route::delete('hotels/{hotel}/galleries/{gallery}', [AdminHotelController::class, 'destroyGallery']);

    Route::apiResource('hotels.rooms', AdminHotelRoomController::class);

    // Travel Packages
    Route::apiResource('travel-packages', AdminTravelPackageController::class)->parameters(['travel-packages' => 'package']);
    Route::put('travel-packages/{package}/assign-manager', [AdminTravelPackageController::class, 'assignManager']);
    Route::post('travel-packages/{package}/galleries', [AdminTravelPackageController::class, 'storeGallery']);
    Route::delete('travel-packages/{package}/galleries/{gallery}', [AdminTravelPackageController::class, 'destroyGallery']);

    // ✅ PERBAIKAN DI SINI
    Route::post('travel-packages/{package}/schedules', [AdminTravelPackageController::class, 'scheduleStore']);
    Route::put('travel-packages/{package}/schedules/{schedule}', [AdminTravelPackageController::class, 'scheduleUpdate']);
    Route::delete('travel-packages/{package}/schedules/{schedule}', [AdminTravelPackageController::class, 'scheduleDestroy']);

    // Events
    Route::apiResource('events', AdminEventController::class);
    Route::post('events/{event}/galleries', [AdminEventController::class, 'storeGallery']);
    Route::delete('events/{event}/galleries/{gallery}', [AdminEventController::class, 'destroyGallery']);

    // Transport Tickets
    Route::apiResource('transport-tickets', AdminTransportTicketController::class);

    // UMKM Approval
    Route::get('umkms/pending', [AdminUmkmController::class, 'pending']);
    Route::get('umkms/rejected', [AdminUmkmController::class, 'rejected']);
    Route::put('umkms/{umkm}/approve', [AdminUmkmController::class, 'approve']);
    Route::put('umkms/{umkm}/reject', [AdminUmkmController::class, 'reject']);
    Route::post('umkms/{umkm}/photo', [AdminUmkmController::class, 'uploadPhoto']);
    Route::apiResource('umkms', AdminUmkmController::class)->except(['store']);

    // Product Approval
    Route::get('products/pending', [AdminProductController::class, 'pending']);
    Route::get('products/rejected', [AdminProductController::class, 'rejected']);
    Route::put('products/{product}/approve', [AdminProductController::class, 'approve']);
    Route::put('products/{product}/reject', [AdminProductController::class, 'reject']);
    Route::apiResource('products', AdminProductController::class)->except(['store']);

    // Bookings
    Route::get('bookings', [AdminBookingController::class, 'index']);
    Route::get('bookings/{booking}', [AdminBookingController::class, 'show']);

    // Orders (UMKM)
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::get('orders/{order}', [AdminOrderController::class, 'show']);

    // Reviews
    Route::get('reviews', [AdminReviewController::class, 'index']);
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy']);

    // Webhook Simulation (Admin only, testing/local environment)
    if (app()->environment(['local', 'testing'])) {
        Route::post('wallet/simulate-webhook/{orderId}', [WalletController::class, 'simulateWebhook']);
    }
});

/*
|--------------------------------------------------------------------------
| MANAGER ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('manager')->middleware(['auth:sanctum', 'manager'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [ManagerDashboardController::class, 'index']);

    // Destinations (hanya miliknya)
    Route::apiResource('destinations', ManagerDestinationController::class);
    Route::post('destinations/{destination}/galleries', [ManagerDestinationController::class, 'storeGallery']);
    Route::delete('destinations/{destination}/galleries/{gallery}', [ManagerDestinationController::class, 'destroyGallery']);
    Route::put('destinations/{destination}/facilities', [ManagerDestinationController::class, 'syncFacilities']);

    // Hotels (hanya miliknya)
    Route::apiResource('hotels', ManagerHotelController::class);
    Route::post('hotels/{hotel}/galleries', [ManagerHotelController::class, 'storeGallery']);
    Route::delete('hotels/{hotel}/galleries/{gallery}', [ManagerHotelController::class, 'destroyGallery']);

    // Hotel Rooms (di hotel miliknya)
    Route::post('hotels/{hotel}/rooms', [ManagerHotelRoomController::class, 'store']);
    Route::put('hotels/{hotel}/rooms/{room}', [ManagerHotelRoomController::class, 'update']);
    Route::delete('hotels/{hotel}/rooms/{room}', [ManagerHotelRoomController::class, 'destroy']);

    // Travel Packages (hanya miliknya)
    Route::apiResource('travel-packages', ManagerTravelPackageController::class)->parameters(['travel-packages' => 'package']);
    Route::post('travel-packages/{package}/galleries', [ManagerTravelPackageController::class, 'storeGallery']);
    Route::delete('travel-packages/{package}/galleries/{gallery}', [ManagerTravelPackageController::class, 'destroyGallery']);
    Route::post('travel-packages/{package}/schedules', [ManagerTravelPackageController::class, 'scheduleStore']);
    Route::put('travel-packages/{package}/schedules/{schedule}', [ManagerTravelPackageController::class, 'scheduleUpdate']);
    Route::delete('travel-packages/{package}/schedules/{schedule}', [ManagerTravelPackageController::class, 'scheduleDestroy']);

    // Transportations (hanya miliknya)
    Route::apiResource('transportations', ManagerTransportationController::class);
    Route::post('transportations/{transportation}/galleries', [ManagerTransportationController::class, 'storeGallery']);
    Route::delete('transportations/{transportation}/galleries/{gallery}', [ManagerTransportationController::class, 'destroyGallery']);

    // Events (hanya di destination miliknya)
    Route::apiResource('events', ManagerEventController::class);
    Route::post('events/{event}/galleries', [ManagerEventController::class, 'storeGallery']);
    Route::delete('events/{event}/galleries/{gallery}', [ManagerEventController::class, 'destroyGallery']);

    // Bookings (hanya yang masuk ke entitas miliknya)
    Route::get('bookings', [ManagerBookingController::class, 'index']);
    Route::get('bookings/{booking}', [ManagerBookingController::class, 'show']);
    Route::put('bookings/{booking}/update-status', [ManagerBookingController::class, 'updateStatus']);
});

/*
|--------------------------------------------------------------------------
| UMKM ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('umkm')->middleware(['auth:sanctum', 'umkm'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [UmkmDashboardController::class, 'index']);

    // Profil UMKM
    Route::get('/profile', [UmkmProfileController::class, 'show']);
    Route::post('/profile', [UmkmProfileController::class, 'store']);
    Route::put('/profile', [UmkmProfileController::class, 'update']);

    // Products
    Route::apiResource('products', UmkmProductController::class);

    // Product Images
    Route::post('products/{product}/images', [UmkmProductImageController::class, 'store']);
    Route::delete('products/{product}/images/{image}', [UmkmProductImageController::class, 'destroy']);

    // Orders
    Route::get('orders', [UmkmOrderController::class, 'index']);
    Route::get('orders/{order}', [UmkmOrderController::class, 'show']);
    Route::put('orders/{order}/update-status', [UmkmOrderController::class, 'updateStatus']);
});