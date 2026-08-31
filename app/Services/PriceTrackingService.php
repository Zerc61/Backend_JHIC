<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PriceHistory;
use App\Models\WishlistItem;

/**
 * Lacak harga wishlist: simpan snapshot price_histories, deteksi penurunan
 * >= 5% atau tercapainya target_price → notifikasi (throttle 24 jam).
 */
class PriceTrackingService
{
    private const DROP_THRESHOLD_PERCENT = 5;

    private const MAX_HISTORY_POINTS = 60;

    public function __construct(private CatalogPriceResolver $resolver)
    {
    }

    public function trackAll(): array
    {
        $report = ['tracked' => 0, 'drops' => 0, 'targets' => 0, 'snapshots' => 0];

        WishlistItem::with(['collection'])->chunk(200, function ($items) use (&$report) {
            foreach ($items as $item) {
                $result = $this->trackItem($item);
                if (! $result) {
                    continue;
                }

                $report['tracked']++;
                $report['snapshots'] += $result['snapshots'];
                $report['drops'] += $result['drops'];
                $report['targets'] += $result['targets'];
            }
        });

        return $report;
    }

    /**
     * @return array{snapshots: int, drops: int, targets: int}|null
     */
    public function trackItem(WishlistItem $item): ?array
    {
        $type = CatalogPriceResolver::typeFromClass($item->wishlistable_type);
        if (! $type) {
            return null;
        }

        $resolved = $this->resolver->resolve($type, $item->wishlistable_id);
        if (! $resolved || $resolved['price'] <= 0) {
            return null;
        }

        $newPrice = round($resolved['price'], 2);
        $report = ['snapshots' => 0, 'drops' => 0, 'targets' => 0];

        PriceHistory::create([
            'wishlist_item_id' => $item->id,
            'price' => $newPrice,
            'recorded_at' => now(),
        ]);
        $report['snapshots']++;

        $this->pruneHistory($item->id);

        $last = PriceHistory::where('wishlist_item_id', $item->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->skip(1)
            ->first();

        if ($last) {
            $oldPrice = (float) $last->price;
            $dropPct = $oldPrice > 0 ? (($oldPrice - $newPrice) / $oldPrice) * 100 : 0;

            if ($newPrice < $oldPrice && $dropPct >= self::DROP_THRESHOLD_PERCENT) {
                $dropPct = round($dropPct, 1);
                if (! $this->recentlyNotified($item, 'price_drop')) {
                    Notification::createPriceDrop($item, $oldPrice, $newPrice);
                    $report['drops']++;
                }
                $item->forceFill([
                    'note' => $item->note ?? 'Turun ' . $dropPct . '% dari ' . number_format($oldPrice, 0, ',', '.'),
                ])->save();
            }
        }

        if ($item->target_price !== null && $newPrice <= (float) $item->target_price) {
            if (! $this->recentlyNotified($item, 'price_target')) {
                Notification::createPriceTarget($item, $newPrice);
                $report['targets']++;
            }
        }

        return $report;
    }

    private function recentlyNotified(WishlistItem $item, string $type): bool
    {
        return Notification::byType($type)
            ->where('user_id', $item->collection?->user_id)
            ->where('data->wishlist_item_id', $item->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();
    }

    private function pruneHistory(int $itemId): void
    {
        $keepIds = PriceHistory::where('wishlist_item_id', $itemId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(self::MAX_HISTORY_POINTS)
            ->pluck('id');

        PriceHistory::where('wishlist_item_id', $itemId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}