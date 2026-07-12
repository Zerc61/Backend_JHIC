<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDetail = $this->relationLoaded('galleries');

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'thumbnail'       => $this->thumbnail,
            'type'            => $this->type,
            'capacity'        => $this->capacity,
            'price_per_day'   => (float) $this->price_per_day,
            'includes_driver' => $this->includes_driver,
            'includes_fuel'   => $this->includes_fuel,

            'description' => $this->when($isDetail, $this->description),
            'phone'       => $this->when($isDetail, $this->phone),

            'destination' => $this->when($this->relationLoaded('destination'), [
                'id'   => $this->destination?->id,
                'name' => $this->destination?->name,
                'slug' => $this->destination?->slug,
            ]),

            'galleries' => $this->when($isDetail, fn () => $this->galleries->map(fn ($g) => [
                'id'      => $g->id,
                'image'   => $g->image,
                'caption' => $g->caption,
            ])),

            'reviews_avg'   => $this->when($this->relationLoaded('reviews'), fn () => round($this->reviews->avg('rating') ?? 0, 1)),
            'reviews_count'  => $this->when($this->relationLoaded('reviews'), fn () => $this->reviews->count()),

            'is_wishlisted' => $this->when(
                auth()->check() && $this->relationLoaded('wishlists'),
                fn () => $this->wishlists->isNotEmpty()
            ),
        ];
    }
}