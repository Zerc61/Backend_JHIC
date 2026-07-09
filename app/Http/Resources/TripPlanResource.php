<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'budget' => (float) $this->budget,
            'budget_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->budget),
            'duration_days' => $this->duration_days,
            'total_people' => $this->total_people,
            'estimated_cost' => (float) $this->estimated_cost,
            'estimated_cost_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->estimated_cost),
            'itinerary' => $this->itinerary,
            'created_at' => $this->created_at->toIso8601String(),
            'destinations' => DestinationResource::collection($this->whenLoaded('destinations')),
        ];
    }
}