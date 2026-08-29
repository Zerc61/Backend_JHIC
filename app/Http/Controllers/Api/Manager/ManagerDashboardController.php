<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\TravelPackage;
use App\Models\Transportation;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $managerId = auth()->id();

        // Gunakan query builder untuk menghindari clone berlebihan
        $destinations = Destination::where('manager_id', $managerId);
        $hotels = Hotel::where('manager_id', $managerId);
        $packages = TravelPackage::where('manager_id', $managerId);

        // Ambil semua ID
        $hotelIds = (clone $hotels)->pluck('id');
        $destinationIds = (clone $destinations)->pluck('id');
        $packageIds = (clone $packages)->pluck('id');

        // Booking yang terkait dengan manager (pakai union agar lebih efisien)
        $hotelBookings = Booking::where('booking_type', 'hotel')
            ->whereHas('hotelBooking', fn($q) => $q->whereIn('hotel_id', $hotelIds));

        $destinationBookings = Booking::where('booking_type', 'destination_ticket')
            ->whereHas('destinationTicketBooking', fn($q) => $q->whereIn('destination_id', $destinationIds));

        $packageBookings = Booking::where('booking_type', 'travel_package')
            ->whereHas('packageBooking', fn($q) => $q->whereIn('travel_package_id', $packageIds));

        // Gabungkan dengan union (atau orWhere)
        $allBookings = Booking::where(function ($q) use ($hotelIds, $destinationIds, $packageIds) {
            $q->where('booking_type', 'hotel')
                ->whereHas('hotelBooking', fn($hq) => $hq->whereIn('hotel_id', $hotelIds))
              ->orWhere('booking_type', 'destination_ticket')
                ->whereHas('destinationTicketBooking', fn($dq) => $dq->whereIn('destination_id', $destinationIds))
              ->orWhere('booking_type', 'travel_package')
                ->whereHas('packageBooking', fn($pq) => $pq->whereIn('travel_package_id', $packageIds));
        });

        // Total rooms
        $totalRooms = DB::table('hotel_rooms')
            ->join('hotels', 'hotel_rooms.hotel_id', '=', 'hotels.id')
            ->where('hotels.manager_id', $managerId)
            ->sum('hotel_rooms.total_rooms');

        // Event
        $events = Event::where('created_by', $managerId);

        // Transportasi
        $transportations = Transportation::where('manager_id', $managerId);

        return response()->json([
            'destinations' => [
                'total' => $destinations->count(),
                'published' => (clone $destinations)->where('status', 'published')->count(),
                'draft' => (clone $destinations)->where('status', 'draft')->count(),
            ],
            'hotels' => [
                'total' => $hotels->count(),
                'published' => (clone $hotels)->where('status', 'published')->count(),
                'draft' => (clone $hotels)->where('status', 'draft')->count(),
                'total_rooms' => $totalRooms,
            ],
            'travel_packages' => [
                'total' => $packages->count(),
                'published' => (clone $packages)->where('status', 'published')->count(),
                'draft' => (clone $packages)->where('status', 'draft')->count(),
            ],
            'transportation' => [
                'total' => $transportations->count(),
                'published' => (clone $transportations)->where('status', 'published')->count(),
                'draft' => (clone $transportations)->where('status', 'draft')->count(),
            ],
            'events' => [
                'total' => $events->count(),
                'upcoming' => (clone $events)->where('status', 'upcoming')->count(),
                'ongoing' => (clone $events)->where('status', 'ongoing')->count(),
            ],
            'bookings' => [
                'total' => $allBookings->count(),
                'pending' => (clone $allBookings)->where('status', 'pending')->count(),
                'paid' => (clone $allBookings)->where('status', 'paid')->count(),
                'completed' => (clone $allBookings)->where('status', 'completed')->count(),
                'cancelled' => (clone $allBookings)->where('status', 'cancelled')->count(),
                'total_revenue' => (clone $allBookings)->whereIn('status', ['paid', 'confirmed', 'completed'])->sum('total_price'),
            ],
        ]);
    }
}