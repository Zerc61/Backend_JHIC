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
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'duration_days' => $this->duration_days,
            'total_people' => $this->total_people,
            'estimated_cost' => (float) $this->estimated_cost,
            'estimated_cost_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->estimated_cost),
            'is_public' => (bool) $this->is_public,
            'share_token' => $this->share_token,
            'share_url' => $this->share_token ? url('/share/trip/' . $this->share_token) : null,
            'itinerary' => $this->itinerary,
            'days' => ItineraryDayResource::collection($this->whenLoaded('days')),
            'created_at' => $this->created_at?->toIso8601String(),
            'destinations' => DestinationResource::collection($this->whenLoaded('destinations')),
        ];
    }
}