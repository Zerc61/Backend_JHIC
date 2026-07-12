<?php
// app/Http/Resources/TravelPackageResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDetail = $this->relationLoaded('galleries');

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'thumbnail'        => $this->thumbnail,
            'duration_days'    => $this->duration_days,
            'duration_nights'  => $this->duration_nights,
            'price_per_person' => (float) $this->price_per_person,

            'next_schedule' => $this->when(
                $this->relationLoaded('schedules'),
                fn () => ($avail = $this->schedules->first(fn ($s) => $s->isAvailable()))
                    ? [
                        'id'              => $avail->id,
                        'departure_date'  => $avail->departure_date?->format('Y-m-d'),
                        'return_date'     => $avail->return_date?->format('Y-m-d'),
                        'remaining_slots' => $avail->getRemainingSlots(),
                    ]
                    : null
            ),

            // Detail only
            'description'      => $this->when($isDetail, $this->description),
            'included_items'   => $this->when($isDetail, $this->included_items ?? []),
            'excluded_items'   => $this->when($isDetail, $this->excluded_items ?? []),
            'meals_included'   => $this->when($isDetail, $this->meals_included ?? []),
            'benefits'         => $this->when($isDetail, $this->benefits ?? []),  // ← BARU
            'terms_conditions' => $this->when($isDetail, $this->terms_conditions),

            'destination' => $this->when($this->relationLoaded('destination'), [
                'id'   => $this->destination?->id,
                'name' => $this->destination?->name,
                'slug' => $this->destination?->slug,
            ]),

            'hotel' => $this->when($isDetail && $this->relationLoaded('hotel'), [
                'id'   => $this->hotel?->id,
                'name' => $this->hotel?->name,
                'slug' => $this->hotel?->slug,
            ]),

            // ← BLOK TRANSPORTATION DIHAPUS

            'galleries' => $this->when($isDetail, fn () => $this->galleries->map(fn ($g) => [
                'id'      => $g->id,
                'image'   => $g->image,
                'caption' => $g->caption,
            ])),

            'schedules' => $this->when($isDetail, fn () => $this->schedules->map(fn ($s) => [
                'id'              => $s->id,
                'departure_date'  => $s->departure_date?->format('Y-m-d'),
                'return_date'     => $s->return_date?->format('Y-m-d'),
                'max_capacity'    => $s->max_capacity,
                'current_booked'  => $s->current_booked,
                'remaining_slots' => $s->getRemainingSlots(),
                'is_available'    => $s->isAvailable(),
                'notes'           => $s->notes,
                // ← pickup_location, pickup_time, vehicle_info, driver_name, driver_phone DIHAPUS
            ])),

            'is_wishlisted' => $this->when(
                auth()->check() && $this->relationLoaded('wishlists'),
                fn () => $this->wishlists->isNotEmpty()
            ),
        ];
    }
}