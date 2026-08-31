<?php

namespace App\Http\Resources;

use App\Services\CatalogPriceResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = CatalogPriceResolver::typeFromClass($this->wishlistable_type) ?? 'destination';
        $resolved = app(CatalogPriceResolver::class)->resolve($type, $this->wishlistable_id);

        $history = $this->whenLoaded('priceHistories');
        $price = $resolved['price'] ?? 0;
        $dropPct = 0;
        $isDropping = false;

        if ($history) {
            $points = collect($history);
            if ($points->count() >= 2) {
                $current = (float) $points->last()->price;
                $prev = (float) $points[$points->count() - 2]->price;
                $price = $current;
                if ($prev > 0 && $current < $prev) {
                    $dropPct = round((($prev - $current) / $prev) * 100, 1);
                    $isDropping = $dropPct >= 5;
                }
            }
        }

        return [
            'id' => $this->id,
            'collection_id' => $this->collection_id,
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'reference_id' => $this->wishlistable_id,
            'name' => $resolved['name'] ?? $this->wishlistable?->name ?? 'Item',
            'image' => $resolved['image'] ?? null,
            'current_price' => round($price, 2),
            'current_price_formatted' => \App\Helpers\GeneralHelper::formatRupiah(round($price, 2)),
            'target_price' => $this->target_price !== null ? (float) $this->target_price : null,
            'target_price_formatted' => $this->target_price !== null
                ? \App\Helpers\GeneralHelper::formatRupiah((float) $this->target_price)
                : null,
            'note' => $this->note,
            'drop_percent' => $dropPct,
            'is_price_dropped' => $isDropping,
            'history' => $history
                ? collect($history)->take(-10)->map(fn ($h) => [
                    'recorded_at' => $h->recorded_at->toIso8601String(),
                    'price' => (float) $h->price,
                ])->values()
                : [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function typeLabel(string $type): string
    {
        return [
            'hotel' => 'Hotel',
            'destination' => 'Wisata',
            'umkm' => 'Produk UMKM',
            'transport' => 'Transport',
            'package' => 'Paket Wisata',
        ][$type] ?? $type;
    }
}