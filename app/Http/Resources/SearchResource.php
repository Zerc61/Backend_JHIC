<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this['image'] ?? null;
        if ($image && ! str_starts_with($image, 'http')) {
            $image = url('storage/' . $image);
        }

        return [
            'type' => $this['type'],
            'type_label' => $this['type_label'],
            'id' => $this['id'],
            'name' => $this['name'],
            'slug' => $this['slug'] ?? null,
            'description' => $this['description'] ?? null,
            'image' => $image,
            'address' => $this['address'] ?? null,
            'lat' => $this['lat'] ?? null,
            'lng' => $this['lng'] ?? null,
            'price' => $this['price'] !== null ? (float) $this['price'] : null,
            'price_formatted' => $this->formatPrice($this['price']),
            'rating' => $this['rating'] !== null ? round((float) $this['rating'], 1) : null,
            'rating_count' => (int) ($this['rating_count'] ?? 0),
            'distance' => $this['distance'] !== null ? round((float) $this['distance'], 2) : null,
            'category' => $this['category'] ?? null,
            'url' => $this['url'],
            'extra' => $this['extra'] ?? [],
            'created_at' => isset($this['created_at']) ? \Illuminate\Support\Carbon::createFromTimestamp($this['created_at'])->toIso8601String() : null,
        ];
    }

    private function formatPrice($raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $price = (float) $raw;

        if ($price == 0) {
            return 'Gratis';
        }

        return \App\Helpers\GeneralHelper::formatRupiah($price);
    }
}