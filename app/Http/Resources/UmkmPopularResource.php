<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UmkmPopularResource extends JsonResource
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

            'average_rating' => round((float) ($this->reviews_avg_rating ?? 0), 1),
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'products_count' => (int) ($this->products_count ?? 0),

            // Cover: produk pertama atau foto UMKM
            'cover_image' => $this->products?->first()?->image ?? $this->photo,

            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
                'icon' => $this->category?->icon,
            ]),

            'destination' => $this->whenLoaded('destination', fn () => [
                'id' => $this->destination?->id,
                'name' => $this->destination?->name,
                'slug' => $this->destination?->slug,
            ]),

            // Hotel terdekat (dihitung di controller via haversine)
            'nearest_hotel' => $this->nearest_hotel ?? null,

            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}