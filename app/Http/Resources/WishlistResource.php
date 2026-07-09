<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'added_at' => $this->created_at->toIso8601String(),
            'destination' => new DestinationResource($this->whenLoaded('destination')),
        ];
    }
}