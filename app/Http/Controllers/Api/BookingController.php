<?php

// app/Http/Controllers/Api/BookingController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MyBookingResource;
use App\Models\Booking;
use App\Models\CoinTransaction;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\HotelRoom;
use App\Models\PackageBooking;
use App\Models\PackageBookingItem;
use App\Models\TransportTicket;
use App\Models\TransportTicketBooking;
use App\Models\TravelPackage;
use App\Models\TravelPackageSchedule;
use App\Models\Transportation;
use App\Models\TransportationBooking;
use App\Models\Wallet;
use App\Services\TransportTicket\TransportTicketServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingController extends Controller
{
    public function __construct(
        private TransportTicketServiceInterface $ticketService
    ) {}

    // ================================================================
    // LIST BOOKING USER
    // ================================================================
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->forUser(auth()->id())
            ->with($this->detailRelations())
            ->when($request->type, fn ($q, $t) => $q->where('booking_type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => MyBookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'per_page'     => $bookings->perPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    // ================================================================
    // DETAIL BOOKING (DENGAN QR)
    // ================================================================
    public function show(string $bookingNumber): JsonResponse
    {
        $booking = Booking::query()
            ->forUser(auth()->id())
            ->with($this->detailRelations())
            ->where('booking_number', $bookingNumber)
            ->firstOrFail();

        return response()->json([
            'data' => new MyBookingResource($booking),
        ]);
    }

    // ================================================================
    // CREATE BOOKING
    // ================================================================
    public function store(Request $request): JsonResponse
    {
        $rules = match ($request->booking_type) {
            'hotel' => [
                'hotel_id'        => 'required|exists:hotels,id',
                'hotel_room_id'   => 'required|exists:hotel_rooms,id',
                'check_in_date'   => 'required|date|after:today',
                'check_out_date'  => 'required|date|after:check_in_date',
                'number_of_rooms' => 'required|integer|min:1',
                'number_of_guests'=> 'required|integer|min:1',
                'guest_name'      => 'required|string|max:255',
                'guest_phone'     => 'required|string|max:20',
                'special_requests'=> 'nullable|string',
            ],
            'transportation' => [
                'transportation_id' => 'required|exists:transportations,id',
                'start_date'        => 'required|date|after:today',
                'end_date'          => 'required|date|after:start_date',
                'pickup_location'   => 'nullable|string|max:255',
            ],
            'transport_ticket' => [
                'transport_ticket_id' => 'required|exists:transport_tickets,id',
                'passengers'          => 'required|array|min:1|max:9',
                'passengers.*.name'   => 'required|string|max:255',
                'passengers.*.id_type'   => 'required|in:KTP,Passport,SIM',
                'passengers.*.id_number' => 'required|string|max:50',
            ],
            'travel_package' => [
                'travel_package_id' => 'required|exists:travel_packages,id',
                'schedule_id'       => 'required|exists:travel_package_schedules,id',
                'total_travelers'   => 'required|integer|min:1',
                'traveler_names'    => 'required|array|min:1',
                'traveler_names.*'  => 'required|string|max:255',
                'contact_person'    => 'required|string|max:255',
                'contact_phone'     => 'required|string|max:20',
                'notes'             => 'nullable|string',
            ],
            default => ['booking_type' => 'required|in:hotel,transportation,transport_ticket,travel_package'],
        };

        $request->validate(array_merge([
            'booking_type' => 'required|in:hotel,transportation,transport_ticket,travel_package',
            'notes'        => 'nullable|string',
        ], $rules));

        $totalPrice = $this->calculatePrice($request);

        $rate = 2000;
        $coinAmount = $totalPrice / $rate;
        $wallet = auth()->user()->wallet;

        if (!$wallet || $wallet->balance < $coinAmount) {
            throw ValidationException::withMessages([
                'balance' => 'Saldo NusaCoin tidak mencukupi. Dibutuhkan ' . number_format($coinAmount, 1) . ' Coin.',
            ]);
        }

        return DB::transaction(function () use ($request, $totalPrice, $coinAmount, $rate, $wallet) {
            $balanceBefore = $wallet->balance;

            $booking = Booking::create([
                'user_id'            => auth()->id(),
                'booking_type'       => $request->booking_type,
                'status'             => 'paid',
                'total_price'        => $totalPrice,
                'coin_amount'        => $coinAmount,
                'coin_to_rupiah_rate'=> $rate,
                'rupiah_equivalent'  => $totalPrice,
                'notes'              => $request->notes,
                'paid_at'            => now(),
            ]);

            $this->createBookingDetail($booking, $request);

            $wallet->decrement('balance', $coinAmount);

            CoinTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => 'debit',
                'amount'         => $coinAmount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $wallet->fresh()->balance,
                'description'    => "Booking {$booking->booking_number} ({$booking->booking_type})",
                'reference_type' => Booking::class,
                'reference_id'   => $booking->id,
            ]);

            $booking->load($this->detailRelations());

            return response()->json([
                'message' => 'Booking berhasil dibuat',
                'data'    => new MyBookingResource($booking),
            ], 201);
        });
    }

    // ================================================================
    // CANCEL BOOKING
    // ================================================================
    public function cancel(string $bookingNumber): JsonResponse
    {
        $booking = Booking::query()
            ->forUser(auth()->id())
            ->where('booking_number', $bookingNumber)
            ->whereIn('status', ['pending', 'paid'])
            ->firstOrFail();

        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
            ]);

            match ($booking->booking_type) {
                'hotel' => $booking->hotelBooking?->update(['status' => 'cancelled']),
                'transportation' => $booking->transportationBooking?->update(['status' => 'cancelled']),
                'transport_ticket' => $booking->ticketBookings->each(fn ($tb) => $tb->update(['status' => 'cancelled'])),
                'travel_package' => $booking->packageBooking?->update(['status' => 'cancelled']),
            };

            $wallet = $booking->user->wallet;
            $balanceBefore = $wallet->balance;
            $wallet->increment('balance', $booking->coin_amount);

            CoinTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => 'credit',
                'amount'         => $booking->coin_amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $wallet->fresh()->balance,
                'description'    => "Refund booking {$booking->booking_number}",
                'reference_type' => Booking::class,
                'reference_id'   => $booking->id,
            ]);

            if ($booking->booking_type === 'travel_package' && $booking->packageBooking) {
                $schedule = $booking->packageBooking->schedule;
                $schedule->decrement('current_booked', $booking->packageBooking->total_travelers);
                if ($schedule->status === 'full') {
                    $schedule->update(['status' => 'available']);
                }
            }

            if ($booking->booking_type === 'transport_ticket') {
                $booking->ticketBookings->each(function ($tb) {
                    $tb->transportTicket->increment('available_seats');
                });
            }

            return response()->json([
                'message' => 'Booking berhasil dibatalkan. NusaCoin telah dikembalikan.',
            ]);
        });
    }

    // ================================================================
    // HELPERS
    // ================================================================
    private function detailRelations(): array
    {
        return [
            'hotelBooking.hotel',
            'hotelBooking.room',
            'transportationBooking.transportation',
            'ticketBookings.transportTicket',
            'packageBooking.travelPackage',
            'packageBooking.schedule',
            'packageBooking.items',
        ];
    }

    private function calculatePrice(Request $request): float
    {
        return match ($request->booking_type) {
            'hotel'           => $this->calculateHotelPrice($request),
            'transportation'  => $this->calculateTransportPrice($request),
            'transport_ticket' => $this->calculateTicketPrice($request),
            'travel_package'  => $this->calculatePackagePrice($request),
        };
    }

    private function calculateHotelPrice(Request $request): float
    {
        $room = HotelRoom::where('id', $request->hotel_room_id)
            ->where('hotel_id', $request->hotel_id)
            ->where('status', 'available')
            ->firstOrFail();

        $nights = Carbon::parse($request->check_in_date)
            ->diffInDays(Carbon::parse($request->check_out_date));

        return $room->price_per_night * $nights * $request->number_of_rooms;
    }

    private function calculateTransportPrice(Request $request): float
    {
        $transport = Transportation::where('id', $request->transportation_id)
            ->where('status', 'published')
            ->firstOrFail();

        $days = Carbon::parse($request->start_date)
            ->diffInDays(Carbon::parse($request->end_date));

        $days = max(1, $days);

        return $transport->price_per_day * $days;
    }

    private function calculateTicketPrice(Request $request): float
    {
        $ticket = TransportTicket::where('id', $request->transport_ticket_id)
            ->where('status', 'available')
            ->firstOrFail();

        $passengerCount = count($request->passengers);

        if (!$ticket->hasEnoughSeats($passengerCount)) {
            throw ValidationException::withMessages([
                'passengers' => "Hanya tersedia {$ticket->available_seats} kursi.",
            ]);
        }

        return $ticket->price_per_ticket * $passengerCount;
    }

    private function calculatePackagePrice(Request $request): float
    {
        $package = TravelPackage::where('id', $request->travel_package_id)
            ->where('status', 'published')
            ->firstOrFail();

        return $package->price_per_person * $request->total_travelers;
    }

    private function createBookingDetail(Booking $booking, Request $request): void
    {
        match ($booking->booking_type) {
            'hotel'           => $this->createHotelBooking($booking, $request),
            'transportation'  => $this->createTransportationBooking($booking, $request),
            'transport_ticket' => $this->createTicketBooking($booking, $request),
            'travel_package'  => $this->createPackageBooking($booking, $request),
        };
    }

    private function createHotelBooking(Booking $booking, Request $request): void
    {
        $overlapping = HotelBooking::where('hotel_room_id', $request->hotel_room_id)
            ->where('status', '!=', 'cancelled')
            ->where('check_in_date', '<', $request->check_out_date)
            ->where('check_out_date', '>', $request->check_in_date)
            ->sum('number_of_rooms');

        $room = HotelRoom::find($request->hotel_room_id);
        $available = $room->total_rooms - $overlapping;

        if ($available < $request->number_of_rooms) {
            throw ValidationException::withMessages([
                'rooms' => "Hanya tersedia {$available} kamar untuk tanggal tersebut.",
            ]);
        }

        $qrContent = implode("\n", [
            'NusaTrip - Hotel Check-in',
            'Booking: ' . $booking->booking_number,
            'Hotel: ' . Hotel::find($request->hotel_id)->name,
            'Room: ' . $room->name,
            'Check-in: ' . $request->check_in_date,
            'Check-out: ' . $request->check_out_date,
            'Rooms: ' . $request->number_of_rooms,
            'Guests: ' . $request->number_of_guests,
            'Name: ' . $request->guest_name,
        ]);

        $qrFileName = 'HOTEL-' . $booking->booking_number . '.svg';
        $qrPath = 'qrcodes/' . $qrFileName;
        QrCode::format('svg')->size(300)->margin(1)->generate($qrContent, public_path($qrPath));

        HotelBooking::create([
            'booking_id'       => $booking->id,
            'hotel_id'         => $request->hotel_id,
            'hotel_room_id'    => $request->hotel_room_id,
            'check_in_date'    => $request->check_in_date,
            'check_out_date'   => $request->check_out_date,
            'number_of_rooms'  => $request->number_of_rooms,
            'number_of_guests' => $request->number_of_guests,
            'guest_name'       => $request->guest_name,
            'guest_phone'      => $request->guest_phone,
            'special_requests' => $request->special_requests,
            'status'           => 'confirmed',
            'qr_code'          => url($qrPath),
        ]);
    }

    private function createTransportationBooking(Booking $booking, Request $request): void
    {
        $transport = Transportation::findOrFail($request->transportation_id);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $days = $start->diffInDays($end);
        $days = max(1, $days);

        TransportationBooking::create([
            'booking_id'      => $booking->id,
            'transportation_id'=> $transport->id,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'number_of_days'  => $days,
            'pickup_location' => $request->pickup_location,
            'notes'           => $request->notes,
            'status'          => 'confirmed',
        ]);
    }

    private function createTicketBooking(Booking $booking, Request $request): void
    {
        $ticket = TransportTicket::findOrFail($request->transport_ticket_id);
        $passengers = $request->passengers;
        $passengerCount = count($passengers);

        $freshTicket = TransportTicket::where('id', $ticket->id)
            ->where('available_seats', '>=', $passengerCount)
            ->lockForUpdate()
            ->firstOrFail();

        $serviceResult = $this->ticketService->book($ticket->id, $passengers);

        $freshTicket->decrement('available_seats', $passengerCount);
        if ($freshTicket->fresh()->available_seats <= 0) {
            $freshTicket->update(['status' => 'sold_out']);
        }

        foreach ($passengers as $index => $pax) {
            $seatNumber = $serviceResult['seat_numbers'][$index] ?? null;
            $ticketNumber = $serviceResult['ticket_numbers'][$index] ?? null;

            $qrContent = implode("\n", [
                'NusaTrip - Boarding Pass',
                'Booking: ' . $booking->booking_number,
                'Provider: ' . $ticket->provider,
                $ticket->transport_mode === 'pesawat' ? 'Flight' : 'Trip',
                ': ' . ($ticket->flight_number ?? $ticket->provider),
                'Route: ' . $ticket->origin_code . ' -> ' . $ticket->destination_code,  // ✅ DIUBAH
                'Date: ' . $ticket->departure_time->format('Y-m-d'),
                'Departure: ' . $ticket->departure_time->format('H:i'),
                'Arrival: ' . $ticket->arrival_time->format('H:i'),
                'Class: ' . $ticket->class_type,
                'Passenger: ' . $pax['name'],
                'Seat: ' . ($seatNumber ?? 'Assigned at counter'),
                'Ticket: ' . ($ticketNumber ?? '-'),
            ]);

            $qrFileName = "TKT-{$booking->booking_number}-" . ($index + 1) . '.svg';
            $qrPath = 'qrcodes/' . $qrFileName;
            QrCode::format('svg')->size(300)->margin(1)->generate($qrContent, public_path($qrPath));

            TransportTicketBooking::create([
                'booking_id'            => $booking->id,
                'transport_ticket_id'   => $ticket->id,
                'passenger_name'        => $pax['name'],
                'passenger_id_type'     => $pax['id_type'],
                'passenger_id_number'   => $pax['id_number'],
                'seat_number'           => $seatNumber,
                'ticket_number'         => $ticketNumber,
                'provider_booking_code' => $serviceResult['provider_booking_code'],
                'qr_code'               => url($qrPath),
                'status'                => $serviceResult['status'],
                'issued_at'             => $serviceResult['issued_at'],
                'raw_response'          => $serviceResult['raw_response'],
            ]);
        }
    }

    private function createPackageBooking(Booking $booking, Request $request): void
    {
        $schedule = TravelPackageSchedule::where('id', $request->schedule_id)
            ->where('travel_package_id', $request->travel_package_id)
            ->firstOrFail();

        $remaining = $schedule->max_capacity - $schedule->current_booked;
        if ($remaining < $request->total_travelers) {
            throw ValidationException::withMessages([
                'travelers' => "Sisa slot tersedia: {$remaining} orang.",
            ]);
        }

        $package = TravelPackage::find($request->travel_package_id);

        $pkgBooking = PackageBooking::create([
            'booking_id'        => $booking->id,
            'travel_package_id' => $request->travel_package_id,
            'schedule_id'       => $request->schedule_id,
            'total_travelers'   => $request->total_travelers,
            'traveler_names'    => $request->traveler_names,
            'contact_person'    => $request->contact_person,
            'contact_phone'     => $request->contact_phone,
            'notes'             => $request->notes,
            'status'            => 'confirmed',
        ]);

        $schedule->increment('current_booked', $request->total_travelers);
        if ($schedule->current_booked >= $schedule->max_capacity) {
            $schedule->update(['status' => 'full']);
        }

        $this->generatePackageItems($pkgBooking, $package, $schedule);
    }

    private function generatePackageItems(PackageBooking $pkgBooking, TravelPackage $package, TravelPackageSchedule $schedule): void
    {
        $sortOrder = 0;
        $departure = $schedule->departure_date->format('Y-m-d');
        $return = $schedule->return_date->format('Y-m-d');
        $guests = $pkgBooking->total_travelers;
        $bookingNum = $pkgBooking->booking->booking_number;
        $contactName = $pkgBooking->contact_person;
        $contactPhone = $pkgBooking->contact_phone;

        // ===== QR HOTEL =====
        if ($package->hotel_id) {
            $hotel = $package->hotel;
            $qrContent = implode("\n", [
                'NusaTrip - Hotel Check-in',
                'Booking: ' . $bookingNum,
                'Paket: ' . $package->name,
                'Hotel: ' . $hotel->name,
                'Check-in: ' . $departure,
                'Check-out: ' . $return,
                'Guests: ' . $guests,
                'CP: ' . $contactName,
                'Phone: ' . $contactPhone,
            ]);

            $qrFileName = 'PKG-HOTEL-' . $bookingNum . '.svg';
            $qrPath = 'qrcodes/' . $qrFileName;
            QrCode::format('svg')->size(300)->margin(1)->generate($qrContent, public_path($qrPath));

            PackageBookingItem::create([
                'package_booking_id' => $pkgBooking->id,
                'item_type'          => 'hotel',
                'title'              => "Hotel: {$hotel->name}",
                'description'        => "Check-in: " . $schedule->departure_date->format('d M Y') . " - Check-out: " . $schedule->return_date->format('d M Y') . " - {$guests} tamu",  // ✅ DIUBAH
                'qr_code'            => url($qrPath),
                'qr_data'            => [
                    'type'       => 'hotel_booking',
                    'booking_id' => $pkgBooking->booking_id,
                    'hotel_id'   => $package->hotel_id,
                    'check_in'   => $departure,
                    'check_out'  => $return,
                    'guests'     => $guests,
                ],
                'sort_order'         => $sortOrder++,
            ]);
        }

        // ===== QR TIKET DESTINASI =====
        $destinations = Destination::where('id', $package->destination_id)->get();
        foreach ($destinations as $dest) {
            $qrContent = implode("\n", [
                'NusaTrip - Tiket Wisata',
                'Booking: ' . $bookingNum,
                'Paket: ' . $package->name,
                'Destinasi: ' . $dest->name,
                'Tanggal: ' . $departure,
                'Pengunjung: ' . $guests . ' orang',
                'CP: ' . $contactName,
                'Phone: ' . $contactPhone,
            ]);

            $qrFileName = 'PKG-TICKET-' . $bookingNum . '-' . $dest->id . '.svg';
            $qrPath = 'qrcodes/' . $qrFileName;
            QrCode::format('svg')->size(300)->margin(1)->generate($qrContent, public_path($qrPath));

            PackageBookingItem::create([
                'package_booking_id' => $pkgBooking->id,
                'item_type'          => 'destination_ticket',
                'title'              => "Tiket: {$dest->name}",
                'description'        => "Valid: " . $schedule->departure_date->format('d M Y') . " - {$guests} orang",  // ✅ DIUBAH
                'qr_code'            => url($qrPath),
                'qr_data'            => [
                    'type'           => 'destination_ticket',
                    'booking_id'     => $pkgBooking->booking_id,
                    'destination_id' => $dest->id,
                    'date'           => $departure,
                    'guests'         => $guests,
                ],
                'sort_order'         => $sortOrder++,
            ]);
        }

        // ===== INFO MAKAN (tanpa QR, informasi saja) =====
        if ($package->meals_included && count($package->meals_included) > 0) {
            foreach ($package->meals_included as $meal) {
                PackageBookingItem::create([
                    'package_booking_id' => $pkgBooking->id,
                    'item_type'          => 'meal',
                    'title'              => "[Meal] {$meal}",  // ✅ DIUBAH
                    'description'        => "Termasuk dalam paket untuk {$guests} orang",
                    'qr_code'            => null,
                    'qr_data'            => null,
                    'sort_order'         => $sortOrder++,
                ]);
            }
        }

        // ===== INFO BENEFIT (tanpa QR, informasi saja) =====
        if ($package->benefits && count($package->benefits) > 0) {
            foreach ($package->benefits as $benefit) {
                PackageBookingItem::create([
                    'package_booking_id' => $pkgBooking->id,
                    'item_type'          => 'benefit',
                    'title'              => "[Benefit] {$benefit}",  // ✅ DIUBAH
                    'description'        => "Benefit khusus dari paket ini",
                    'qr_code'            => null,
                    'qr_data'            => null,
                    'sort_order'         => $sortOrder++,
                ]);
            }
        }
    }
}