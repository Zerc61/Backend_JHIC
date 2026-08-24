<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\Review;
use App\Models\TransportTicket;
use App\Models\Transportation;
use App\Models\TravelPackage;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    private const PAID_BOOKING_STATUSES = ['paid', 'confirmed', 'completed'];
    private const COMPLETED_ORDER_STATUSES = ['paid', 'preparing', 'ready', 'picked_up'];

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_users' => User::count(),
                'total_transactions' => (float) (
                    Booking::whereIn('status', self::PAID_BOOKING_STATUSES)->sum('total_price')
                    + Order::whereIn('status', self::COMPLETED_ORDER_STATUSES)->sum('total_price')
                ),
                'total_bookings' => Booking::count(),
                'pending_umkm' => Umkm::where('status', 'pending')->count(),

                'destinations_count' => Destination::count(),
                'hotels_count' => Hotel::count(),
                'packages_count' => TravelPackage::count(),
                'events_count' => Event::count(),
                'transportation_count' => Transportation::count(),
                'tickets_count' => TransportTicket::count(),

                'new_users_24h' => User::where('created_at', '>=', now()->subDay())->count(),
                'transactions_24h' => (float) (
                    Booking::whereIn('status', self::PAID_BOOKING_STATUSES)
                        ->where('created_at', '>=', now()->subDay())->sum('total_price')
                    + Order::whereIn('status', self::COMPLETED_ORDER_STATUSES)
                        ->where('created_at', '>=', now()->subDay())->sum('total_price')
                ),
                'bookings_24h' => Booking::where('created_at', '>=', now()->subDay())->count(),

                'recent_users' => User::latest()
                    ->limit(10)
                    ->get(['id', 'name', 'email', 'role', 'status']),

                'pending_umkms' => Umkm::where('status', 'pending')
                    ->latest()
                    ->limit(10)
                    ->get(['id', 'name', 'description']),

                'recent_bookings' => Booking::latest()
                    ->limit(10)
                    ->get(['id', 'booking_number', 'booking_type', 'total_price', 'status', 'created_at']),

                'recent_reviews' => Review::with(['user:id,name', 'reviewable'])
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(fn (Review $review) => [
                        'id' => $review->id,
                        'user_name' => $review->user?->name ?? 'Pengguna',
                        'destination_name' => $review->reviewable?->name ?? '-',
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                    ]),
            ],
        ]);
    }
}
