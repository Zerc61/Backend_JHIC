<?php

// app/Http/Resources/TransportTicketDetailResource.php

namespace App\Http\Resources;

use App\Enums\TransportMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportTicketDetailResource extends JsonResource
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
            'origin'           => [
                'code' => $this->origin_code,
                'name' => $this->origin_name,
            ],
            'destination'      => [
                'code' => $this->destination_code,
                'name' => $this->destination_name,
            ],
            'flight_number'    => $this->flight_number,
            'departure_time'   => $this->departure_time->toIso8601String(),
            'arrival_time'     => $this->arrival_time->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'duration'         => $this->getDurationLabel(),
            'is_transit'       => $this->is_transit,
            'transit_info'     => $this->transit_info,
            'class_type'       => $this->class_type,
            'available_seats'  => $this->available_seats,
            'price_per_ticket' => (float) $this->price_per_ticket,
            'price_formatted'  => \App\Helpers\GeneralHelper::formatRupiah((float) $this->price_per_ticket),
            'status'           => $this->status,
            'valid_until'      => $this->valid_until?->toIso8601String(),
        ];
    }
}
