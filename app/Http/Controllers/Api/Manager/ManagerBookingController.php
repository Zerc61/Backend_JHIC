<?php

namespace App\Http\Controllers\Api\Manager;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\TravelPackage;
use App\Models\Transportation;
use App\Enums\BookingStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ManagerBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $managerId = auth()->id();

        $hotelIds = Hotel::where('manager_id', $managerId)->pluck('id');
        $destinationIds = Destination::where('manager_id', $managerId)->pluck('id');
        $packageIds = TravelPackage::where('manager_id', $managerId)->pluck('id');
        $transportationIds = Transportation::where('manager_id', $managerId)->pluck('id');

        $query = Booking::with(['user'])
            ->where(function ($q) use ($hotelIds, $destinationIds, $packageIds, $transportationIds) {
                // Booking hotel milik manager
                $q->where('booking_type', 'hotel')
                  ->whereHas('hotelBooking', fn($hq) => $hq->whereIn('hotel_id', $hotelIds))

                // Booking tiket destination milik manager
                ->orWhere('booking_type', 'destination_ticket')
                  ->whereHas('destinationTicketBooking', fn($dq) => $dq->whereIn('destination_id', $destinationIds))

                // Booking travel package milik manager
                ->orWhere('booking_type', 'travel_package')
                  ->whereHas('packageBooking', fn($pq) => $pq->whereIn('travel_package_id', $packageIds))

                // Booking transportasi sewaan milik manager
                ->orWhere('booking_type', 'transportation')
                  ->whereHas('transportationBooking', fn($tq) => $tq->whereIn('transportation_id', $transportationIds));
            });

        // Filter tambahan
        if ($request->filled('booking_type')) {
            $query->where('booking_type', $request->booking_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('booking_number', 'like', "%{$request->search}%");
        }

        $bookings = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        // Load detail relasi per tipe
        $bookings->transform(function ($booking) {
            $booking->load($this->getDetailRelation($booking->booking_type));
            return $booking;
        });

        return response()->json($bookings);
    }

    public function show(Booking $booking): JsonResponse
    {
        if (!$this->isBookingOwnedByManager($booking)) {
            abort(403, 'Anda tidak memiliki akses ke booking ini.');
        }

        $booking->load(['user', $this->getDetailRelation($booking->booking_type)]);

        return response()->json(['data' => $booking]);
    }

  public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        if (!$this->isBookingOwnedByManager($booking)) {
            abort(403, 'Anda tidak memiliki akses ke booking ini.');
        }

        $validated = $request->validate([
            'status' => ['required', new Enum(BookingStatus::class)],
            'notes' => 'nullable|string',
        ]);

        // Periksa apakah status diperbolehkan untuk tipe booking ini
        $allowedStatuses = $this->getAllowedStatuses($booking->booking_type);
        if (!in_array($validated['status'], $allowedStatuses)) {
            return response()->json([
                'message' => "Status tidak valid. Pilihan: " . implode(', ', $allowedStatuses),
            ], 422);
        }

        // Update status detail berdasarkan tipe
        match ($booking->booking_type) {
            'hotel' => $this->updateHotelBooking($booking, $validated['status']),
            'destination_ticket' => $this->updateDestinationTicketBooking($booking, $validated['status']),
            'travel_package' => $this->updatePackageBooking($booking, $validated['status']),
            default => null,
        };

        // Update master booking status
        $masterStatus = $this->mapDetailStatusToMaster($validated['status'], $booking->booking_type);
        $booking->update([
            'status' => $masterStatus,
            'cancelled_at' => $validated['status'] === 'cancelled' ? now() : null,
        ]);

        $booking->load(['user', $this->getDetailRelation($booking->booking_type)]);

        return response()->json([
            'message' => 'Status booking berhasil diupdate.',
            'data' => $booking,
        ]);
    }

    private function mapDetailStatusToMaster(string $detailStatus, string $type): string
    {
        return match ($type) {
            'hotel' => match ($detailStatus) {
                'checked_out' => 'completed',
                'cancelled' => 'cancelled',
                default => 'confirmed',
            },
            'destination_ticket' => match ($detailStatus) {
                'used' => 'completed',
                'cancelled' => 'cancelled',
                default => 'confirmed',
            },
            'travel_package' => match ($detailStatus) {
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                default => 'confirmed',
            },
            'transportation' => match ($detailStatus) {
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                default => 'confirmed',
            },
            default => $detailStatus,
        };
    }

    private function isBookingOwnedByManager(Booking $booking): bool
    {
        $managerId = auth()->id();

        return match ($booking->booking_type) {
            'hotel' => $booking->hotelBooking && Hotel::where('id', $booking->hotelBooking->hotel_id)->where('manager_id', $managerId)->exists(),
            'destination_ticket' => $booking->destinationTicketBooking && Destination::where('id', $booking->destinationTicketBooking->destination_id)->where('manager_id', $managerId)->exists(),
            'travel_package' => $booking->packageBooking && TravelPackage::where('id', $booking->packageBooking->travel_package_id)->where('manager_id', $managerId)->exists(),
            'transportation' => $booking->transportationBooking && Transportation::where('id', $booking->transportationBooking->transportation_id)->where('manager_id', $managerId)->exists(),
            default => false,
        };
    }

    private function getAllowedStatuses(string $type): array
    {
        return match ($type) {
            'hotel' => ['confirmed', 'checked_in', 'checked_out', 'cancelled'],
            'destination_ticket' => ['confirmed', 'used', 'cancelled'],
            'travel_package' => ['confirmed', 'completed', 'cancelled'],
            'transportation' => ['confirmed', 'completed', 'cancelled'],
            default => [],
        };
    }

    private function updateHotelBooking(Booking $booking, string $status): void
    {
        $booking->hotelBooking?->update(['status' => $status]);
    }

    private function updateDestinationTicketBooking(Booking $booking, string $status): void
    {
        $booking->destinationTicketBooking?->update(['status' => $status]);
    }

    private function updatePackageBooking(Booking $booking, string $status): void
    {
        $booking->packageBooking?->update(['status' => $status]);
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