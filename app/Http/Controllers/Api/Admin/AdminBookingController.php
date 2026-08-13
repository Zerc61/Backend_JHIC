<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['user']);

        if ($request->filled('booking_type')) {
            $query->where('booking_type', $request->booking_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('booking_number', 'like', "%{$search}%");
        }

        $bookings = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        // Load detail berdasarkan tipe
        $bookings->transform(function ($booking) {
            $booking->load($this->getDetailRelation($booking->booking_type));
            return $booking;
        });

        return response()->json($bookings);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load(['user', $this->getDetailRelation($booking->booking_type)]);

        return response()->json(['data' => $booking]);
    }

    private function getDetailRelation(string $type): string
    {
        return match ($type) {
            'hotel' => 'hotelBooking.room',
            'transport_ticket' => 'ticketBookings.transportTicket',
            'travel_package' => 'packageBooking.travelPackage',
            'destination_ticket' => 'destinationTicketBooking.destination',
            'transportation' => 'transportationBooking.transportation',
            default => '',
        };
    }
}