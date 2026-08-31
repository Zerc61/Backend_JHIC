<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');
        $withPrices = $this->whenLoaded('items.priceHistories');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_default' => (bool) $this->is_default,
            'is_public' => (bool) $this->is_public,
            'share_token' => $this->share_token,
            'share_url' => $this->share_token ? url('/share/wishlist/' . $this->share_token) : null,
            'items_count' => $items ? $items->count() : $this->items()->count(),
            'cover_image' => $items && $items->isNotEmpty()
                ? (app(\App\Services\CatalogPriceResolver::class)->resolve(
                    \App\Services\CatalogPriceResolver::typeFromClass($items->first()->wishlistable_type) ?? 'destination',
                    $items->first()->wishlistable_id
                )['image'] ?? null)
                : null,
            'estimated_cost' => $withPrices
                ? round($items->sum(fn ($item) => app(\App\Services\CatalogPriceResolver::class)->resolve(
                    \App\Services\CatalogPriceResolver::typeFromClass($item->wishlistable_type) ?? 'destination',
                    $item->wishlistable_id
                )['price'] ?? 0), 2)
                : 0,
            'items' => WishlistItemResource::collection($items ?: []),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}