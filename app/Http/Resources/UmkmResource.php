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
            'average_rating' => round($this->average_rating ?? 0, 1),
            'status' => $this->status->value,

            // Cover diambil dari produk pertama (hanya jika products di-load)
            'cover_image' => $this->when(
                $this->relationLoaded('products') && $this->products->isNotEmpty(),
                fn() => $this->products->first()->image
            ),

            // Category — pakai resource jika di-load, inline jika tidak
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'icon' => $this->category->icon,
            ]),

            // Destination
            'destination' => $this->whenLoaded('destination', fn() => [
                'id' => $this->destination->id,
                'name' => $this->destination->name,
            ]),

            // Products — hanya muncul jika di-load (aman untuk halaman lain)
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}