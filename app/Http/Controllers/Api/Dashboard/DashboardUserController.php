<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\WalletResource;
use App\Http\Resources\CoinTransactionResource;
use App\Http\Resources\TransportTicketResource;
use App\Http\Resources\HotelResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\Dashboard\DashboardDestinationResource;
use App\Models\TransportTicket;
use App\Models\Hotel;
use App\Models\Destination;
use App\Models\Event;
use App\Enums\DestinationStatus;
use App\Enums\EventStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardUserController extends Controller
{
    /**
     * Dashboard utama (public + user-specific)
     * 
     * GET /api/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // =============================================
        // 1. USER + WALLET (hanya jika login)
        // =============================================
        $userData = null;
        $walletData = null;
        $recentTransactions = collect();

        if ($user) {
            $user->load('wallet');
            $userData = new UserResource($user);

            if ($user->wallet) {
                $walletData = new WalletResource($user->wallet);
                $recentTransactions = $user->wallet->coinTransactions()
                    ->latest()
                    ->take(5)
                    ->get();
            }
        }

        // =============================================
        // 2. TRANSPORT DEALS (6 random, upcoming)
        // =============================================
        $transportDeals = TransportTicket::query()
            ->available()
            ->upcoming()
            ->inRandomOrder()
            ->take(6)
            ->get();

        // Sort by departure time biar rapi di UI
        $transportDeals = $transportDeals->sortBy('departure_time')->values();

        // =============================================
        // 3. POPULAR HOTELS (3 random, with min price)
        // =============================================
        $hotels = Hotel::query()
            ->published()
            ->with([
                'destination',
                'rooms' => fn ($q) => $q
                    ->where('status', 'available')
                    ->select('id', 'hotel_id', 'price_per_night', 'status'),
            ])
            ->inRandomOrder()
            ->take(3)
            ->get();

        // =============================================
        // 4. DESTINATIONS (4 random, lengkap)
        // =============================================
        $destinations = Destination::query()
            ->where('status', DestinationStatus::PUBLISHED)
            ->with(['category', 'galleries', 'facilities'])
            ->inRandomOrder()
            ->take(4)
            ->get();

        // =============================================
        // 5. UPCOMING EVENTS (3, terdekat)
        // =============================================
        $events = Event::query()
            ->with('destination')
            ->where('status', EventStatus::UPCOMING)
            ->orderBy('start_date', 'asc')
            ->take(3)
            ->get();

        // =============================================
        // RESPONSE
        // =============================================
        return response()->json([
            'data' => [
                'user'                => $userData,
                'wallet'              => $walletData,
                'recent_transactions' => CoinTransactionResource::collection($recentTransactions),
                'transport_deals'     => TransportTicketResource::collection($transportDeals),
                'popular_hotels'     => HotelResource::collection($hotels),
                'destinations'        => DashboardDestinationResource::collection($destinations),
                'upcoming_events'     => EventResource::collection($events),
            ],
        ]);
    }
}