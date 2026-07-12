<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'               => $this->id,
            'booking_number'   => $this->booking_number,
            'booking_type'     => $this->booking_type,
            'status'           => $this->status,
            'total_price'      => (float) $this->total_price,
            'coin_amount'      => (float) $this->coin_amount,
            'rupiah_equivalent' => (float) $this->rupiah_equivalent,
            'paid_at'          => $this->paid_at?->toISOString(),
            'cancelled_at'     => $this->cancelled_at?->toISOString(),
            'created_at'       => $this->created_at->toISOString(),
        ];

        // Hotel
        if ($this->booking_type === 'hotel' && $this->relationLoaded('hotelBooking')) {
            $data['hotel_detail'] = [
                'hotel_name'      => $this->hotelBooking->hotel?->name,
                'room_name'       => $this->hotelBooking->room?->name,
                'check_in_date'   => $this->hotelBooking->check_in_date?->format('Y-m-d'),
                'check_out_date'  => $this->hotelBooking->check_out_date?->format('Y-m-d'),
                'number_of_rooms'  => $this->hotelBooking->number_of_rooms,
                'number_of_guests' => $this->hotelBooking->number_of_guests,
                'guest_name'      => $this->hotelBooking->guest_name,
                'guest_phone'     => $this->hotelBooking->guest_phone,
                'qr_code'         => $this->hotelBooking->qr_code,
                'booking_status'  => $this->hotelBooking->status,
            ];
        }

        // Transport Ticket
        if ($this->booking_type === 'transport_ticket' && $this->relationLoaded('ticketBookings')) {
            $firstTicket = $this->ticketBookings->first()?->transportTicket;
            $data['ticket_detail'] = [
                'provider'         => $firstTicket?->provider,
                'transport_mode'   => $firstTicket?->transport_mode,
                'flight_number'    => $firstTicket?->flight_number,
                'origin_code'      => $firstTicket?->origin_code,
                'origin_name'      => $firstTicket?->origin_name,
                'destination_code' => $firstTicket?->destination_code,
                'destination_name' => $firstTicket?->destination_name,
                'departure_time'   => $firstTicket?->departure_time?->format('Y-m-d H:i'),
                'arrival_time'     => $firstTicket?->arrival_time?->format('Y-m-d H:i'),
                'class_type'       => $firstTicket?->class_type,
                'provider_booking_code' => $this->ticketBookings->first()?->provider_booking_code,
                'passengers'       => $this->ticketBookings->map(fn ($tb) => [
                    'name'         => $tb->passenger_name,
                    'id_type'      => $tb->passenger_id_type,
                    'seat_number'  => $tb->seat_number,
                    'ticket_number'=> $tb->ticket_number,
                    'qr_code'      => $tb->qr_code,
                    'status'       => $tb->status,
                ]),
            ];
        }

        // Paket Wisata
        if ($this->booking_type === 'travel_package' && $this->relationLoaded('packageBooking')) {
            $data['package_detail'] = [
                'package_name'    => $this->packageBooking->travelPackage?->name,
                'departure_date'  => $this->packageBooking->schedule?->departure_date?->format('Y-m-d'),
                'return_date'     => $this->packageBooking->schedule?->return_date?->format('Y-m-d'),
                'total_travelers' => $this->packageBooking->total_travelers,
                'traveler_names'  => $this->packageBooking->traveler_names,
                'contact_person'  => $this->packageBooking->contact_person,
                'contact_phone'   => $this->packageBooking->contact_phone,
                'booking_status'  => $this->packageBooking->status,
                'items'           => $this->packageBooking->items->map(fn ($item) => [
                    'type'       => $item->item_type,
                    'title'      => $item->title,
                    'description' => $item->description,
                    'qr_code'    => $item->qr_code,
                ]),
            ];
        }

        return $data;
    }
}