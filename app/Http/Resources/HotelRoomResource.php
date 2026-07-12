<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'description'     => $this->description,
            'capacity'        => $this->capacity,
            'price_per_night' => (float) $this->price_per_night,
            'total_rooms'     => $this->total_rooms,
            'amenities'       => $this->amenities ?? [],
            'status'          => $this->status,
        ];
    }
}