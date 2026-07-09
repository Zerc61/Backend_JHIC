<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripPlanResource;
use App\Models\Destination;
use App\Models\TripPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripPlanController extends Controller
{
    // ========================================================
    // KONSTANTA ESTIMASI BIAYA (bisa dipindah ke config nanti)
    // ========================================================
    private const ESTIMATED_WISATA_PER_DEST = 25000;   // Rp 25.000 per destinasi
    private const ESTIMATED_MAKAN_PER_DAY  = 75000;   // Rp 75.000 per orang per hari
    private const ESTIMATED_TRANSPORT_PER_DAY = 50000; // Rp 50.000 per orang per hari
    private const ESTIMATED_HOTEL_PER_NIGHT = 150000;  // Rp 150.000 per orang per malam

    public function index(Request $request): JsonResponse
    {
        $plans = TripPlan::with('destinations')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => TripPlanResource::collection($plans)]);
    }

    /**
     * SMART TRIP PLANNER - Buat rencana perjalanan
     * 
     * Logika "smart":
     * - Auto-hitung estimasi biaya (wisata + makan + transport + hotel)
     * - Generate itinerary JSON terstruktur per hari
     * - Jika destinasi punya field estimated_cost, pakai itu
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'budget'        => 'nullable|numeric|min:0',
            'duration_days' => 'required|integer|min:1|max:30',
            'total_people'  => 'required|integer|min:1|max:100',
            'destinations'  => 'required|array|min:1',
            'destinations.*.id'         => 'required|exists:destinations,id',
            'destinations.*.day_number' => 'required|integer|min:1|max:30',
            'destinations.*.sort_order' => 'nullable|integer|min:0',
            'destinations.*.notes'      => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $duration = $request->duration_days;
        $people   = $request->total_people;
        $budget   = $request->budget ?? 0;

        // ========================================================
        // 1. Ambil semua destinasi yang dipilih + hitung biaya wisata
        // ========================================================
        $destinationIds = collect($request->destinations)->pluck('id');
        $destinations   = Destination::whereIn('id', $destinationIds)->get()->keyBy('id');

        $wisataCost = 0;
        foreach ($destinationIds as $id) {
            // Prioritas: field estimated_cost di DB, fallback ke konstanta
            $destCost = $destinations[$id]->estimated_cost ?? self::ESTIMATED_WISATA_PER_DEST;
            $wisataCost += $destCost;
        }
        $wisataCostTotal = $wisataCost * $people;

        // ========================================================
        // 2. Hitung biaya tambahan (makan, transport, hotel)
        // ========================================================
        $makanCost     = self::ESTIMATED_MAKAN_PER_DAY * $duration * $people;
        $transportCost = self::ESTIMATED_TRANSPORT_PER_DAY * $duration * $people;
        $hotelNights   = max($duration - 1, 0);
        $hotelCost     = self::ESTIMATED_HOTEL_PER_NIGHT * $hotelNights * $people;

        $totalEstimated = $wisataCostTotal + $makanCost + $transportCost + $hotelCost;

        // ========================================================
        // 3. Build itinerary JSON (terstruktur per hari)
        // ========================================================
        $itinerary = [];
        foreach ($request->destinations as $dest) {
            $d = $destinations[$dest['id']];
            $dayKey = 'day_' . $dest['day_number'];

            if (!isset($itinerary[$dayKey])) {
                $itinerary[$dayKey] = [];
            }

            $itinerary[$dayKey][] = [
                'destination_id'   => $d->id,
                'destination_name' => $d->name,
                'destination_slug' => $d->slug ?? null,
                'destination_image'=> $d->image ?? null,
                'sort_order'       => $dest['sort_order'] ?? 0,
                'notes'            => $dest['notes'] ?? null,
                'estimated_cost'   => $d->estimated_cost ?? self::ESTIMATED_WISATA_PER_DEST,
            ];
        }

        // Sort setiap hari berdasarkan sort_order
        foreach ($itinerary as $day => &$items) {
            usort($items, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
        }
        unset($items);

        // ========================================================
        // 4. Buat TripPlan
        // ========================================================
        $tripPlan = TripPlan::create([
            'user_id'        => $user->id,
            'title'          => $request->title,
            'budget'         => $budget,
            'duration_days'  => $duration,
            'total_people'   => $people,
            'estimated_cost' => $totalEstimated,
            'itinerary'      => $itinerary,
        ]);

        // ========================================================
        // 5. Attach destinasi ke pivot table
        // ========================================================
        $syncData = [];
        foreach ($request->destinations as $dest) {
            $syncData[$dest['id']] = [
                'day_number' => $dest['day_number'],
                'sort_order' => $dest['sort_order'] ?? 0,
                'notes'      => $dest['notes'] ?? null,
            ];
        }
        $tripPlan->destinations()->attach($syncData);

        // ========================================================
        // 6. Return response
        // ========================================================
        $tripPlan->load('destinations');

        return response()->json([
            'message' => 'Rencana perjalanan berhasil dibuat!',
            'data'    => new TripPlanResource($tripPlan),
        ], 201);
    }

    public function show(TripPlan $tripPlan): JsonResponse
    {
        $this->authorize('view', $tripPlan);
        $tripPlan->load('destinations');

        return response()->json(['data' => new TripPlanResource($tripPlan)]);
    }

    public function destroy(TripPlan $tripPlan): JsonResponse
    {
        $this->authorize('delete', $tripPlan);
        $tripPlan->delete();

        return response()->json(['message' => 'Rencana perjalanan dihapus']);
    }

    /**
     * Endpoint tambahan: Ambil destinasi yang tersedia untuk planner
     * (ringan, hanya field yang dibutuhkan)
     */
   /**
 * Endpoint tambahan: Ambil destinasi yang tersedia untuk planner
 */
public function availableDestinations(Request $request): JsonResponse
{
    $query = Destination::with('category')
        ->where('status', \App\Enums\DestinationStatus::PUBLISHED);

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%");
        });
    }

    $destinations = $query->select('id', 'name', 'slug', 'address', 'ticket_price')
        ->orderBy('name')
        ->limit(30)
        ->get()
        ->map(function ($dest) {
            return [
                'id'           => $dest->id,
                'name'         => $dest->name,
                'slug'         => $dest->slug,
                'address'      => $dest->address,
                'main_image'   => $dest->main_image ? url("storage/{$dest->main_image}") : null,
                'ticket_price' => (float) $dest->ticket_price,
            ];
        });

    return response()->json(['data' => $destinations]);
}
}