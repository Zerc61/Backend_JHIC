<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDetail = $this->relationLoaded('rooms');

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'thumbnail'    => $this->thumbnail,
            'star_rating'  => $this->star_rating,
            'address'      => $this->address,
            'min_price'    => (float) ($this->whenLoaded('rooms')
                ? $this->rooms->where('status', 'available')->min('price_per_night') ?? 0
                : ($this->min_price ?? 0)),

            // Hanya muncul di detail
            'description'     => $this->when($isDetail, $this->description),
            'phone'           => $this->when($isDetail, $this->phone),
            'website'         => $this->when($isDetail, $this->website),
            'check_in_time'   => $this->when($isDetail, fn () => $this->check_in_time?->format('H:i')),
            'check_out_time'  => $this->when($isDetail, fn () => $this->check_out_time?->format('H:i')),
            'latitude'        => $this->when($isDetail, $this->latitude),
            'longitude'       => $this->when($isDetail, $this->longitude),

            'destination' => $this->when($this->relationLoaded('destination'), [
                'id'   => $this->destination?->id,
                'name' => $this->destination?->name,
                'slug' => $this->destination?->slug,
            ]),

            'rooms'    => $this->when($isDetail, fn () => HotelRoomResource::collection($this->rooms)),
            'galleries' => $this->when($this->relationLoaded('galleries'), fn () => $this->galleries->map(fn ($g) => [
                'id'      => $g->id,
                'image'   => $g->image,
                'caption' => $g->caption,
            ])),

            'reviews_avg'  => $this->when($this->relationLoaded('reviews'), fn () => round($this->reviews->avg('rating') ?? 0, 1)),
            'reviews_count' => $this->when($this->relationLoaded('reviews'), fn () => $this->reviews->count()),

            'is_wishlisted' => $this->when(
                auth()->check() && $this->relationLoaded('wishlists'),
                fn () => $this->wishlists->isNotEmpty()
            ),
        ];
    }
}