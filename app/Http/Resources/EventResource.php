<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'start_date' => $this->start_date->toIso8601String(),
            'end_date' => $this->end_date->toIso8601String(),
            'location' => $this->location,
            'image' => $this->image ? url("storage/{$this->image}") : null,
            'status' => $this->status->value,
            'destination' => $this->whenLoaded('destination', fn() => [
                'id' => $this->destination->id,
                'name' => $this->destination->name,
            ]),
        ];
    }
}