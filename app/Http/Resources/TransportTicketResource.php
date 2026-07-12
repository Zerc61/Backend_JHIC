<?php

// app/Http/Resources/TransportTicketResource.php

namespace App\Http\Resources;

use App\Enums\TransportMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mode = TransportMode::from($this->transport_mode);

        return [
            'id'               => $this->id,
            'provider'         => $this->provider,
            'transport_mode'   => $this->transport_mode,
            'transport_label'  => $mode->label(),
            'transport_icon'   => $mode->icon(),
            'origin_code'      => $this->origin_code,
            'origin_name'      => $this->origin_name,
            'destination_code' => $this->destination_code,
            'destination_name' => $this->destination_name,
            'flight_number'    => $this->flight_number,
            'departure_time'   => $this->departure_time->format('H:i'),
            'departure_date'   => $this->departure_time->format('Y-m-d'),
            'arrival_time'     => $this->arrival_time->format('H:i'),
            'arrival_date'     => $this->arrival_time->format('Y-m-d'),
            'duration'         => $this->getDurationLabel(),
            'is_transit'       => $this->is_transit,
            'transit_info'     => $this->transit_info,
            'class_type'       => $this->class_type,
            'available_seats'  => $this->available_seats,
            'price_per_ticket' => (float) $this->price_per_ticket,
            'price_formatted'  => \App\Helpers\GeneralHelper::formatRupiah((float) $this->price_per_ticket),
            'status'           => $this->status,
        ];
    }
}
