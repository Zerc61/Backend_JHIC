<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Destination;
use App\Models\TransportTicket;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'         => $this->id,
            'created_at' => $this->created_at?->format('d M Y H:i'),
        ];

        if ($this->wishlistable_type === Destination::class) {
            $data['destination'] = [
                'id'                      => $this->wishlistable?->id,
                'name'                    => $this->wishlistable?->name,
                'slug'                    => $this->wishlistable?->slug,
                'main_image'              => $this->wishlistable?->main_image,
                'address'                 => $this->wishlistable?->address,
                'ticket_price_formatted'  => $this->wishlistable?->ticket_price_formatted,
                'category'                => $this->wishlistable?->category ? [
                    'id'   => $this->wishlistable->category->id,
                    'name' => $this->wishlistable->category->name,
                    'icon' => $this->wishlistable->category->icon,
                ] : null,
            ];
        } elseif ($this->wishlistable_type === TransportTicket::class) {
            $data['transport_ticket'] = [
                'id'                  => $this->wishlistable?->id,
                'provider'            => $this->wishlistable?->provider,
                'transport_mode'      => $this->wishlistable?->transport_mode,
                'transport_label'     => $this->wishlistable?->getModeEnum()->label(),
                'transport_icon'      => $this->wishlistable?->getModeEnum()->icon(),
                'origin_code'         => $this->wishlistable?->origin_code,
                'origin_name'         => $this->wishlistable?->origin_name,
                'destination_code'    => $this->wishlistable?->destination_code,
                'destination_name'    => $this->wishlistable?->destination_name,
                'flight_number'       => $this->wishlistable?->flight_number,
                'departure_time'      => $this->wishlistable?->departure_time->format('H:i'),
                'departure_date'      => $this->wishlistable?->departure_time->format('Y-m-d'),
                'arrival_time'        => $this->wishlistable?->arrival_time->format('H:i'),
                'arrival_date'        => $this->wishlistable?->arrival_time->format('Y-m-d'),
                'duration'            => $this->wishlistable?->getDurationLabel(),
                'is_transit'          => $this->wishlistable?->is_transit,
                'transit_info'        => $this->wishlistable?->transit_info,
                'class_type'          => $this->wishlistable?->class_type,
                'available_seats'     => $this->wishlistable?->available_seats,
                'price_per_ticket'    => (float) $this->wishlistable?->price_per_ticket,
                'price_formatted'     => \App\Helpers\GeneralHelper::formatRupiah((float) $this->wishlistable?->price_per_ticket),
            ];
        }

        return $data;
    }
}