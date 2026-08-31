<?php

namespace App\Http\Controllers\Api;

use App\Enums\DestinationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ItineraryItemResource;
use App\Http\Resources\TripPlanResource;
use App\Models\Destination;
use App\Models\ItineraryDay;
use App\Models\ItineraryItem;
use App\Models\TripPlan;
use App\Services\CatalogPriceResolver;
use App\Services\ItineraryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripPlanController extends Controller
{
    public function __construct(
        private ItineraryService $itinerary,
        private CatalogPriceResolver $catalog
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $plans = TripPlan::with(['destinations', 'days.items'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => TripPlanResource::collection($plans)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'             => 'required|string|max:255',
            'budget'            => 'nullable|numeric|min:0',
            'total_people'      => 'nullable|integer|min:1|max:100',
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'duration_days'     => 'nullable|integer|min:1|max:30',
            'destinations'      => 'nullable|array',
            'destinations.*.id' => 'required_with:destinations|exists:destinations,id',
            'destinations.*.day_number' => 'required_with:destinations|integer|min:1|max:30',
            'destinations.*.sort_order' => 'nullable|integer|min:0',
            'destinations.*.notes'      => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $people = $request->total_people ?? 1;

        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->toDateString()
            : now()->toDateString();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->toDateString()
            : Carbon::parse($startDate)->addDays(max(($request->duration_days ?? 1) - 1, 0))->toDateString();

        $tripPlan = TripPlan::create([
            'user_id'        => $user->id,
            'title'          => $request->title,
            'budget'         => $request->budget ?? 0,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'duration_days'  => Carbon::parse($startDate)->diffInDays($endDate) + 1,
            'total_people'   => $people,
            'estimated_cost' => 0,
            'is_public'      => false,
        ]);

        // Dukung payload lama: daftar destinasi + perkiraan biaya klasik
        if ($request->filled('destinations')) {
            $legacy = $this->buildLegacyFromDestinations($request->all(), $tripPlan, $people);
            $tripPlan->forceFill([
                'itinerary'      => $legacy['itinerary'],
                'estimated_cost' => $legacy['cost'],
            ])->save();

            foreach ($legacy['pivot'] as $destinationId => $pivot) {
                $tripPlan->destinations()->attach($destinationId, $pivot);
            }
        }

        $this->itinerary->ensureDays($tripPlan, $startDate, $endDate);
        $this->itinerary->backfillLegacyItinerary($tripPlan->fresh());

        $tripPlan->load(['days.items', 'destinations']);

        return response()->json([
            'message' => 'Rencana perjalanan berhasil dibuat!',
            'data'    => new TripPlanResource($tripPlan),
        ], 201);
    }

    public function show(Request $request, TripPlan $tripPlan): JsonResponse
    {
        $this->authorizeOwner($request, $tripPlan);

        if ($tripPlan->days()->doesntExist() && ! empty($tripPlan->itinerary)) {
            $this->itinerary->backfillLegacyItinerary($tripPlan);
            $tripPlan->refresh();
        }

        $tripPlan->load(['days.items', 'destinations']);

        return response()->json(['data' => new TripPlanResource($tripPlan)]);
    }

    public function update(Request $request, TripPlan $tripPlan): JsonResponse
    {
        $this->authorizeOwner($request, $tripPlan);

        $request->validate([
            'title'        => 'nullable|string|max:255',
            'budget'       => 'nullable|numeric|min:0',
            'total_people' => 'nullable|integer|min:1|max:100',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'is_public'    => 'nullable|boolean',
        ]);

        $rebuild = false;
        if ($request->has('start_date') || $request->has('end_date')) {
            $start = Carbon::parse($request->start_date ?? $tripPlan->start_date);
            $end = Carbon::parse($request->end_date ?? $tripPlan->end_date);
            if ($end->lt($start)) {
                $end = $start->copy();
            }

            $newStart = $start->toDateString();
            $newEnd = $end->toDateString();

            if ($newStart !== $tripPlan->start_date?->toDateString() || $newEnd !== $tripPlan->end_date?->toDateString()) {
                $rebuild = true;
            }

            $tripPlan->forceFill([
                'start_date'    => $newStart,
                'end_date'      => $newEnd,
                'duration_days' => $start->diffInDays($end) + 1,
            ]);
        }

        $tripPlan->fill($request->only(['title', 'budget', 'total_people', 'is_public']));

        if ($request->has('total_people')) {
            $tripPlan->total_people = $request->total_people;
        }

        $tripPlan->save();

        if ($rebuild) {
            $this->itinerary->rebuildDays($tripPlan, $tripPlan->start_date->toDateString(), $tripPlan->end_date->toDateString());
        }

        $this->itinerary->recalculatePlanCost($tripPlan);

        $tripPlan->load(['days.items', 'destinations']);

        return response()->json([
            'message' => 'Rencana berhasil diperbarui.',
            'data'    => new TripPlanResource($tripPlan),
        ]);
    }

    public function destroy(Request $request, TripPlan $tripPlan): JsonResponse
    {
        $this->authorizeOwner($request, $tripPlan);

        $tripPlan->delete();

        return response()->json([
            'message' => 'Rencana perjalanan berhasil dihapus permanen.',
        ]);
    }

    // ── Days ────────────────────────────────────────

    public function storeDay(Request $request, TripPlan $tripPlan): JsonResponse
    {
        $this->authorizeOwner($request, $tripPlan);

        $request->validate([
            'date'       => 'required|date',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $this->itinerary->ensureDays($tripPlan, $request->date);
        $day = ItineraryDay::where('trip_plan_id', $tripPlan->id)
            ->whereDate('date', Carbon::parse($request->date)->toDateString())
            ->first();

        return response()->json([
            'message' => 'Hari baru ditambahkan.',
            'data'    => new TripPlanResource($tripPlan->fresh(['days.items', 'destinations'])),
        ]);
    }

    public function destroyDay(Request $request, ItineraryDay $day): JsonResponse
    {
        $plan = $day->tripPlan;
        $this->authorizeOwner($request, $plan);

        $day->delete();
        $this->itinerary->recalculatePlanCost($plan);

        return response()->json([
            'message' => 'Hari dihapus.',
            'data'    => new TripPlanResource($plan->fresh(['days.items', 'destinations'])),
        ]);
    }

    // ── Items ───────────────────────────────────────

    public function storeItem(Request $request, ItineraryDay $day): JsonResponse
    {
        $plan = $day->tripPlan;
        $this->authorizeOwner($request, $plan);

        $request->validate([
            'slot'            => 'required|in:' . implode(',', ItineraryItem::SLOTS),
            'type'            => 'required|in:' . implode(',', ItineraryItem::TYPES),
            'reference_id'    => 'nullable|integer',
            'custom_name'     => 'nullable|string|max:255',
            'custom_note'     => 'nullable|string|max:1000',
            'custom_cost'     => 'nullable|numeric|min:0',
            'duration_minutes'=> 'nullable|integer|min:1',
        ]);

        $item = $this->itinerary->addItem($day, $request->all());

        return response()->json([
            'message' => 'Item berhasil ditambahkan.',
            'data'    => [
                'id'        => $item->id,
                'day_id'    => $item->day_id,
                'slot'      => $item->slot,
                'type'      => $item->type,
                'name'      => $item->name,
                'image'     => $item->image,
                'estimated_cost' => (float) $item->estimated_cost,
                'estimated_cost_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $item->estimated_cost),
                'estimated_cost_plan' => (float) $plan->fresh()->estimated_cost,
                'lat' => $item->lat,
                'lng' => $item->lng,
            ],
        ]);
    }

    public function updateItem(Request $request, ItineraryItem $item): JsonResponse
    {
        $plan = $item->day->tripPlan;
        $this->authorizeOwner($request, $plan);

        $request->validate([
            'slot'            => 'nullable|in:' . implode(',', ItineraryItem::SLOTS),
            'custom_name'     => 'nullable|string|max:255',
            'custom_note'     => 'nullable|string|max:1000',
            'custom_cost'     => 'nullable|numeric|min:0',
            'duration_minutes'=> 'nullable|integer|min:1',
            'sort_order'      => 'nullable|integer|min:0',
        ]);

        $this->itinerary->updateItem($item, $request->all());

        return response()->json([
            'message' => 'Item berhasil diperbarui.',
            'data'    => new TripPlanResource($plan->fresh(['days.items', 'destinations'])),
        ]);
    }

    public function destroyItem(Request $request, ItineraryItem $item): JsonResponse
    {
        $plan = $item->day->tripPlan;
        $this->authorizeOwner($request, $plan);

        $this->itinerary->deleteItem($item);

        return response()->json([
            'message' => 'Item dihapus.',
            'data'    => new TripPlanResource($plan->fresh(['days.items', 'destinations'])),
        ]);
    }

    public function reorderItems(Request $request, ItineraryDay $day): JsonResponse
    {
        $plan = $day->tripPlan;
        $this->authorizeOwner($request, $plan);

        $request->validate([
            'items'         => 'required|array',
            'items.*.id'    => 'required|integer',
            'items.*.slot'  => 'nullable|in:' . implode(',', ItineraryItem::SLOTS),
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        $this->itinerary->reorderItems($day, $request->items);

        return response()->json([
            'message' => 'Urutan item diperbarui.',
            'data'    => new TripPlanResource($plan->fresh(['days.items', 'destinations'])),
        ]);
    }

    public function optimizeDay(Request $request, ItineraryDay $day): JsonResponse
    {
        $plan = $day->tripPlan;
        $this->authorizeOwner($request, $plan);

        $optimized = $this->itinerary->optimizeDay($day);

        return response()->json([
            'message' => 'Urutan perjalanan dioptimalkan berdasarkan jarak.',
            'data'    => [
                'id'     => $optimized->id,
                'items'  => ItineraryItemResource::collection($optimized->items),
            ],
        ]);
    }

    // ── Share ───────────────────────────────────────

    public function share(Request $request, TripPlan $tripPlan): JsonResponse
    {
        $this->authorizeOwner($request, $tripPlan);

        $tripPlan->forceFill(['is_public' => true])->save();
        $token = $tripPlan->ensureShareToken();
        $url = url('/share/trip/' . $token);

        return response()->json([
            'message'    => 'Rencana siap dibagikan.',
            'share_url'  => $url,
            'token'      => $token,
            'is_public'  => true,
        ]);
    }

    public function sharedView(Request $request, string $token): JsonResponse
    {
        $tripPlan = TripPlan::where('share_token', $token)
            ->where('is_public', true)
            ->with(['days.items', 'destinations'])
            ->first();

        if (! $tripPlan) {
            abort(404, 'Rencana tidak ditemukan atau tidak dibagikan.');
        }

        return response()->json(['data' => new TripPlanResource($tripPlan)]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'q'    => 'nullable|string|max:120',
            'type' => 'nullable|in:' . implode(',', CatalogPriceResolver::TYPES),
        ]);

        $q = trim($request->q ?? '');

        if ($q) {
            $destination = Destination::with('galleries')
                ->where('status', DestinationStatus::PUBLISHED)
                ->where('name', 'like', "%{$q}%")
                ->limit(8)->get()
                ->map(fn ($d) => $this->suggestRow('destination', $d->name, $d->id, $d->main_image, $d->ticket_price ?? $d->estimated_cost ?? 0, $d->latitude, $d->longitude));

            $hotel = \App\Models\Hotel::with('rooms')
                ->where('status', 'published')
                ->where('name', 'like', "%{$q}%")
                ->limit(8)->get()
                ->map(fn ($h) => $this->suggestRow('hotel', $h->name, $h->id, $h->thumbnail, $h->rooms->min('price_per_night') ?? 0, $h->latitude, $h->longitude));

            $umkm = \App\Models\Product::with('umkm')
                ->where('name', 'like', "%{$q}%")
                ->limit(8)->get()
                ->map(fn ($p) => $this->suggestRow('umkm', $p->name ?: ($p->umkm?->name ?? 'Produk UMKM'), $p->id, $p->image, $p->price ?? 0, $p->umkm?->latitude, $p->umkm?->longitude));

            $transport = \App\Models\TransportTicket::where('origin_name', 'like', "%{$q}%")
                ->orWhere('destination_name', 'like', "%{$q}%")
                ->orWhere('provider', 'like', "%{$q}%")
                ->limit(8)->get()
                ->map(fn ($t) => $this->suggestRow('transport', $t->provider . ' — ' . $t->getRouteLabel(), $t->id, null, $t->price_per_ticket ?? 0, null, null));

            $rows = $destination->merge($hotel)->merge($umkm)->merge($transport);
        } else {
            $rows = collect();
        }

        if ($request->filled('type')) {
            $type = $request->type;
            $rows = $rows->where('type', $type)->values();
        }

        return response()->json(['data' => $rows->values()]);
    }

    public function availableDestinations(Request $request): JsonResponse
    {
        $query = Destination::with(['category', 'galleries'])
            ->where('status', DestinationStatus::PUBLISHED);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $destinations = $query->orderBy('name')->limit(30)->get()->map(function ($dest) {
            return [
                'id'             => $dest->id,
                'name'           => $dest->name,
                'slug'           => $dest->slug,
                'address'        => $dest->address,
                'category'       => $dest->category?->name ?? 'Wisata',
                'image'          => $dest->galleries->first()?->image,
                'ticket_price'   => (float) $dest->ticket_price,
                'estimated_cost' => (float) ($dest->estimated_cost ?? 0),
            ];
        });

        return response()->json(['data' => $destinations]);
    }

    private function suggestRow(
        string $type,
        string $name,
        int $id,
        ?string $image,
        float $price,
        $lat = null,
        $lng = null
    ): array {
        return [
            'type'       => $type,
            'id'         => $id,
            'name'       => $name,
            'image'      => $image,
            'unit_price' => round($price, 2),
            'unit_price_formatted' => \App\Helpers\GeneralHelper::formatRupiah(round($price, 2)),
            'lat'        => $lat ? (float) $lat : null,
            'lng'        => $lng ? (float) $lng : null,
        ];
    }

    private function authorizeOwner(Request $request, TripPlan $tripPlan): void
    {
        if (! $tripPlan->isOwner($request->user()->id)) {
            abort(403, 'Kamu tidak memiliki akses ke rencana ini.');
        }
    }

    private function buildLegacyFromDestinations(array $payload, TripPlan $tripPlan, int $people): array
    {
        $destinationIds = collect($payload['destinations'])->pluck('id');
        $destinations = Destination::with('galleries')->whereIn('id', $destinationIds)->get()->keyBy('id');

        $itinerary = [];
        $wisataCostTotal = 0;

        foreach ($payload['destinations'] as $dest) {
            $d = $destinations[$dest['id']];
            $dayKey = 'day_' . $dest['day_number'];

            $itinerary[$dayKey][] = [
                'destination_id'    => $d->id,
                'destination_name'  => $d->name,
                'destination_slug'  => $d->slug ?? null,
                'destination_image' => $d->galleries->first()?->image ?? null,
                'sort_order'        => $dest['sort_order'] ?? 0,
                'notes'             => $dest['notes'] ?? null,
                'estimated_cost'    => $d->estimated_cost ?? 25000,
            ];

            $wisataCostTotal += $d->estimated_cost ?? 25000;
        }

        foreach ($itinerary as $day => &$items) {
            usort($items, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);
        }
        unset($items);

        $duration = $tripPlan->duration_days;
        $cost = ($wisataCostTotal * $people)
            + (75000 * $duration * $people)
            + (50000 * $duration * $people)
            + (150000 * max($duration - 1, 0) * $people);

        $pivot = [];
        foreach ($payload['destinations'] as $dest) {
            $pivot[$dest['id']] = [
                'day_number' => $dest['day_number'],
                'sort_order' => $dest['sort_order'] ?? 0,
                'notes'      => $dest['notes'] ?? null,
            ];
        }

        return ['itinerary' => $itinerary, 'cost' => $cost, 'pivot' => $pivot];
    }
}