<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'price_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->price),
            'stock' => $this->stock,
            'unit' => $this->unit,
            'image' => $this->image ? url("storage/{$this->image}") : null,
            'is_available' => $this->isAvailable(),
            'average_rating' => round($this->average_rating, 1),
            'status' => $this->status->value,
        ];
    }
}