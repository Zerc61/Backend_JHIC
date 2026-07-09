<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UmkmResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'opening_hours' => $this->opening_hours,
            'average_rating' => round($this->average_rating, 1),
            'status' => $this->status->value,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'destination' => [
                'id' => $this->destination->id,
                'name' => $this->destination->name,
            ],
        ];
    }
}