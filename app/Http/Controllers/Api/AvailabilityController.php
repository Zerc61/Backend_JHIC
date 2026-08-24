<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HotelRoom;
use App\Models\Transportation;
use App\Models\TravelPackageSchedule;
use App\Models\BookingHold;
use App\Models\HotelBooking;
use App\Models\TransportationBooking;
use App\Models\PackageBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function hotelRoomAvailability(Request $request, $roomId): JsonResponse
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $room = HotelRoom::findOrFail($roomId);
        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        // Get bookings for date range
        $bookings = HotelBooking::where('hotel_room_id', $roomId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '<', $checkIn)
                            ->where('check_out_date', '>', $checkOut);
                    });
            })
            ->count();

        // Get holds for date range
        $holds = BookingHold::where('holdable_type', 'HotelRoom')
            ->where('holdable_id', $roomId)
            ->active()
            ->count();

        $bookedCount = $bookings + $holds;
        $available = $room->quantity - $bookedCount;

        // Generate calendar
        $calendar = [];
        $current = $checkIn->copy();
        while ($current->lte($checkOut)) {
            $dayBookings = HotelBooking::where('hotel_room_id', $roomId)
                ->where('status', '!=', 'cancelled')
                ->whereDate('check_in_date', '<=', $current)
                ->whereDate('check_out_date', '>', $current)
                ->count();

            $dayHolds = BookingHold::where('holdable_type', 'HotelRoom')
                ->where('holdable_id', $roomId)
                ->active()
                ->count();

            $isAvailable = ($dayBookings + $dayHolds) < $room->quantity;

            $calendar[] = [
                'date' => $current->format('Y-m-d'),
                'available' => $isAvailable,
                'booked_count' => $dayBookings,
                'held_count' => $dayHolds,
                'capacity' => $room->quantity,
            ];

            $current->addDay();
        }

        return response()->json([
            'data' => [
                'room_id' => $room->id,
                'room_name' => $room->room_type,
                'capacity' => $room->quantity,
                'available' => max(0, $available),
                'total_booked' => $bookedCount,
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
                'calendar' => $calendar,
                'is_available' => $available > 0,
            ],
        ]);
    }

    public function transportationAvailability(Request $request, $transportationId): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $transportation = Transportation::findOrFail($transportationId);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Get bookings for date range
        $bookings = TransportationBooking::where('transportation_id', $transportationId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->count();

        // Get holds
        $holds = BookingHold::where('holdable_type', 'Transportation')
            ->where('holdable_id', $transportationId)
            ->active()
            ->count();

        $bookedCount = $bookings + $holds;
        $available = $transportation->quantity - $bookedCount;

        // Generate calendar
        $calendar = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dayBookings = TransportationBooking::where('transportation_id', $transportationId)
                ->where('status', '!=', 'cancelled')
                ->whereDate('start_date', '<=', $current)
                ->whereDate('end_date', '>', $current)
                ->count();

            $dayHolds = BookingHold::where('holdable_type', 'Transportation')
                ->where('holdable_id', $transportationId)
                ->active()
                ->count();

            $isAvailable = ($dayBookings + $dayHolds) < $transportation->quantity;

            $calendar[] = [
                'date' => $current->format('Y-m-d'),
                'available' => $isAvailable,
                'booked_count' => $dayBookings,
                'held_count' => $dayHolds,
                'capacity' => $transportation->quantity,
            ];

            $current->addDay();
        }

        return response()->json([
            'data' => [
                'transportation_id' => $transportation->id,
                'name' => $transportation->name,
                'type' => $transportation->type,
                'capacity' => $transportation->quantity,
                'available' => max(0, $available),
                'total_booked' => $bookedCount,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'calendar' => $calendar,
                'is_available' => $available > 0,
            ],
        ]);
    }

    public function packageAvailability(Request $request, $packageId): JsonResponse
    {
        $request->validate([
            'schedule_id' => 'required|exists:travel_package_schedules,id',
        ]);

        $schedule = TravelPackageSchedule::findOrFail($request->schedule_id);

        // Get bookings for this schedule
        $bookings = PackageBooking::where('schedule_id', $schedule->id)
            ->where('status', '!=', 'cancelled')
            ->sum('total_travelers');

        // Get holds
        $holds = BookingHold::where('holdable_type', 'PackageSchedule')
            ->where('holdable_id', $schedule->id)
            ->active()
            ->sum('quantity');

        $bookedSlots = $bookings + $holds;
        $availableSlots = $schedule->max_capacity - $bookedSlots;

        return response()->json([
            'data' => [
                'schedule_id' => $schedule->id,
                'package_id' => $schedule->travel_package_id,
                'departure_date' => $schedule->departure_date->format('Y-m-d'),
                'return_date' => $schedule->return_date->format('Y-m-d'),
                'max_capacity' => $schedule->max_capacity,
                'available_slots' => max(0, $availableSlots),
                'booked_slots' => $bookings,
                'held_slots' => $holds,
                'status' => $schedule->status,
                'is_available' => $availableSlots > 0,
            ],
        ]);
    }

    public function getCalendarMonth(Request $request, string $type, $itemId): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2026|max:2050',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = $request->year;
        $month = $request->month;
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $calendar = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $booked = 0;
            $held = 0;
            $capacity = 0;

            if ($type === 'hotel') {
                $room = HotelRoom::findOrFail($itemId);
                $capacity = $room->quantity;
                $booked = HotelBooking::where('hotel_room_id', $itemId)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('check_in_date', '<=', $current)
                    ->whereDate('check_out_date', '>', $current)
                    ->count();
                $held = BookingHold::where('holdable_type', 'HotelRoom')
                    ->where('holdable_id', $itemId)
                    ->active()
                    ->count();
            } elseif ($type === 'transportation') {
                $trans = Transportation::findOrFail($itemId);
                $capacity = $trans->quantity;
                $booked = TransportationBooking::where('transportation_id', $itemId)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('start_date', '<=', $current)
                    ->whereDate('end_date', '>', $current)
                    ->count();
                $held = BookingHold::where('holdable_type', 'Transportation')
                    ->where('holdable_id', $itemId)
                    ->active()
                    ->count();
            }

            $calendar[] = [
                'date' => $current->format('Y-m-d'),
                'day_of_week' => $current->format('l'),
                'available' => $capacity - ($booked + $held) > 0,
                'availability_percentage' => $capacity > 0 ? round(((($booked + $held) / $capacity) * 100)) : 0,
            ];

            $current->addDay();
        }

        return response()->json([
            'data' => [
                'type' => $type,
                'item_id' => $itemId,
                'year' => $year,
                'month' => $month,
                'calendar' => $calendar,
            ],
        ]);
    }
}
