<?php

namespace App\Services;

use App\Models\ItineraryDay;
use App\Models\ItineraryItem;
use App\Models\TripPlan;
use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * Logika itinerary builder v2: hari (day) → item per slot.
 */
class ItineraryService
{
    /** Tipe item yang harganya dikali jumlah orang. */
    private const PER_PERSON_TYPES = ['hotel', 'destination', 'transport', 'package'];

    public function __construct(private CatalogPriceResolver $prices)
    {
    }

    public function day(int $id): ?ItineraryDay
    {
        return ItineraryDay::find($id);
    }

    /**
     * Buat day untuk setiap tanggal dalam rentang (hanya bila belum ada).
     */
    public function ensureDays(TripPlan $plan, string $startDate, ?string $endDate = null): void
    {
        if ($plan->days()->exists()) {
            return;
        }

        $start = Carbon::parse($startDate);
        $end = $endDate ? Carbon::parse($endDate) : $start->copy();
        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $order = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            ItineraryDay::create([
                'trip_plan_id' => $plan->id,
                'date' => $cursor->toDateString(),
                'sort_order' => $order,
            ]);
            $order++;
            $cursor->addDay();
        }

        $plan->days()->get();
    }

    /**
     * Rebuild seluruh day (dan cascade item) sesuai rentang baru.
     */
    public function rebuildDays(TripPlan $plan, string $startDate, string $endDate): void
    {
        $plan->days()->delete();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $order = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            ItineraryDay::create([
                'trip_plan_id' => $plan->id,
                'date' => $cursor->toDateString(),
                'sort_order' => $order,
            ]);
            $order++;
            $cursor->addDay();
        }
    }

    /**
     * Tambah item; biaya dihitung otomatis dari referensi (× jumlah orang
     * untuk hotel/destinasi/transport) atau manual via custom_cost.
     */
    public function addItem(ItineraryDay $day, array $data): ItineraryItem
    {
        $plan = $day->tripPlan;
        $people = max((int) ($plan->total_people ?? 1), 1);
        $type = Arr::get($data, 'type', 'custom');
        $slot = Arr::get($data, 'slot', 'morning');
        $referenceId = Arr::get($data, 'reference_id');

        $name = Arr::get($data, 'custom_name');
        $note = Arr::get($data, 'custom_note');
        $lat = null;
        $lng = null;
        $cost = (float) Arr::get($data, 'custom_cost', 0);

        if ($type !== 'custom' && $referenceId) {
            $resolution = $this->prices->resolve($type, (int) $referenceId);

            if ($resolution) {
                $name = $name ?: $resolution['name'];
                $lat = $resolution['lat'];
                $lng = $resolution['lng'];
                $multiplier = in_array($type, self::PER_PERSON_TYPES, true) ? $people : 1;
                $cost = round($resolution['price'] * $multiplier, 2);
            }
        }

        $maxSort = (int) ItineraryItem::where('day_id', $day->id)->where('slot', $slot)->max('sort_order');

        $item = ItineraryItem::create([
            'day_id' => $day->id,
            'slot' => $slot,
            'type' => $type,
            'name' => $name,
            'image' => isset($resolution) ? $resolution['image'] : null,
            'reference_id' => $type !== 'custom' ? $referenceId : null,
            'custom_name' => $type === 'custom' ? $name : null,
            'custom_note' => $note,
            'estimated_cost' => $cost,
            'duration_minutes' => Arr::get($data, 'duration_minutes'),
            'sort_order' => $maxSort,
            'lat' => $lat,
            'lng' => $lng,
        ]);

        $item->load('day');

        $this->recalculatePlanCost($plan);

        return $item;
    }

    public function updateItem(ItineraryItem $item, array $data): ItineraryItem
    {
        $item->fill([
            'slot' => Arr::get($data, 'slot', $item->slot),
            'custom_name' => Arr::get($data, 'custom_name', $item->custom_name),
            'custom_note' => Arr::get($data, 'custom_note', $item->custom_note),
            'estimated_cost' => Arr::get($data, 'custom_cost', $item->estimated_cost),
            'duration_minutes' => Arr::get($data, 'duration_minutes', $item->duration_minutes),
            'sort_order' => Arr::get($data, 'sort_order', $item->sort_order),
        ])->save();

        $this->recalculatePlanCost($item->day->tripPlan);

        return $item->fresh();
    }

    public function deleteItem(ItineraryItem $item): void
    {
        $plan = $item->day->tripPlan;
        $item->delete();
        $this->recalculatePlanCost($plan);
    }

    /**
     * Reorder item dalam satu day (bulk): [{id, slot, sort_order}].
     */
    public function reorderItems(ItineraryDay $day, array $orders): void
    {
        foreach ($orders as $order) {
            $item = ItineraryItem::where('day_id', $day->id)
                ->find($order['id'] ?? null);

            if (! $item) {
                continue;
            }

            $item->forceFill([
                'slot' => $order['slot'] ?? $item->slot,
                'sort_order' => (int) ($order['sort_order'] ?? $item->sort_order),
            ])->save();
        }

        $this->recalculatePlanCost($day->tripPlan);
    }

    /**
     * Sortir item dalam satu day nearest-neighbor (pakai koordinat yang tersedia).
     */
    public function optimizeDay(ItineraryDay $day): ItineraryDay
    {
        foreach (ItineraryItem::SLOTS as $slot) {
            $this->optimizeSlot($day, $slot);
        }

        $this->recalculatePlanCost($day->tripPlan);

        return $day->fresh(['items']);
    }

    private function optimizeSlot(ItineraryDay $day, string $slot): void
    {
        $all = ItineraryItem::where('day_id', $day->id)->where('slot', $slot)->orderBy('sort_order')->get();

        $withCoord = $all->filter(fn (ItineraryItem $i) => $i->lat !== null && $i->lng !== null)->values();
        $without = $all->filter(fn (ItineraryItem $i) => $i->lat === null || $i->lng === null)->values();

        $ordered = [];
        $remaining = $withCoord->all();
        $current = array_shift($remaining);

        if ($current) {
            $ordered[] = $current;
        }

        while ($current && count($remaining) > 0) {
            $bestKey = null;
            $bestDist = INF;

            foreach ($remaining as $key => $candidate) {
                $dist = $this->distance($current->lat, $current->lng, $candidate->lat, $candidate->lng);
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $bestKey = $key;
                }
            }

            $current = $remaining[$bestKey] ?? null;
            if (! $current) {
                break;
            }

            unset($remaining[$bestKey]);
            $ordered[] = $current;
        }

        $final = array_merge($ordered, array_values($remaining));

        foreach ($final as $i => $item) {
            $item->forceFill(['sort_order' => $i])->save();
        }
    }

    /**
     * Rekalkulasi estimated_cost plan = jumlah biaya semua item.
     */
    public function recalculatePlanCost(TripPlan $plan): float
    {
        $total = (float) $plan->items()->sum('estimated_cost');

        $plan->forceFill(['estimated_cost' => round($total, 2)])->save();

        return $total;
    }

    /**
     * Konversi data lama (itinerary JSON + pivot) menjadi day/items bila plan kosong.
     */
    public function backfillLegacyItinerary(TripPlan $plan): void
    {
        if ($plan->days()->exists() || empty($plan->itinerary)) {
            return;
        }

        $itinerary = is_array($plan->itinerary) ? $plan->itinerary : json_decode((string) $plan->itinerary, true);
        if (! is_array($itinerary)) {
            return;
        }

        $order = 0;
        foreach ($itinerary as $dayKey => $entries) {
            $dayNumber = (int) str_replace('day_', '', (string) $dayKey);
            $date = Carbon::parse($plan->start_date ?? now())->addDays($dayNumber - 1)->toDateString();

            $day = ItineraryDay::create([
                'trip_plan_id' => $plan->id,
                'date' => $date,
                'sort_order' => $order++,
            ]);

            if (! is_array($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                ItineraryItem::create([
                    'day_id' => $day->id,
                    'slot' => ItineraryItem::SLOTS[0],
                    'type' => 'destination',
                    'name' => $entry['destination_name'] ?? 'Destinasi',
                    'image' => $entry['destination_image'] ?? null,
                    'reference_id' => $entry['destination_id'] ?? null,
                    'custom_note' => $entry['notes'] ?? null,
                    'estimated_cost' => $entry['estimated_cost'] ?? 0,
                    'sort_order' => $entry['sort_order'] ?? 0,
                ]);
            }
        }

        $this->recalculatePlanCost($plan);
    }

    private function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}