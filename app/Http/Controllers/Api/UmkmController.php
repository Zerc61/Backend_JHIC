<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UmkmResource;
use App\Http\Resources\UmkmPopularResource;
use App\Models\Umkm;
use App\Models\UmkmCategory;
use App\Models\Hotel;
use App\Enums\UmkmStatus;
use App\Enums\ProductStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UmkmController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(['data' => UmkmCategory::orderBy('name')->get()]);
    }

    public function byDestination(string $destinationSlug, Request $request): JsonResponse
    {
        $umkms = Umkm::with(['category', 'destination'])
            ->whereHas('destination', fn($q) => $q->where('slug', $destinationSlug)->where('status', \App\Enums\DestinationStatus::PUBLISHED))
            ->where('status', UmkmStatus::ACTIVE)
            ->when($request->category, fn($q, $cat) => $q->whereHas('category', fn($q2) => $q2->where('slug', $cat)))
            ->get();

        return response()->json(['data' => UmkmResource::collection($umkms)]);
    }

    public function popular(Request $request): JsonResponse
    {
        $query = Umkm::query()
            ->where('status', UmkmStatus::ACTIVE)
            ->with([
                'category',
                'destination:id,name,slug',
                'products' => fn ($q) => $q
                    ->where('status', ProductStatus::AVAILABLE)
                    ->orderBy('created_at', 'desc')
                    ->take(3),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount(['reviews', 'products']);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%"));
        }

        // "Populer": rating bagus → banyak review → banyak produk
        $query
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('products_count');

        $umkms = $query
            ->paginate((int) $request->get('per_page', 50))
            ->withQueryString();

        // Hotel dimuat sekali, hitung yang terdekat via haversine per UMKM
        $hotels = Hotel::query()
            ->published()
            ->with([
                'destination:id,name',
                'rooms' => fn ($q) => $q
                    ->where('status', 'available')
                    ->select('id', 'hotel_id', 'price_per_night', 'status'),
            ])
            ->get(['id', 'name', 'slug', 'thumbnail', 'star_rating', 'latitude', 'longitude']);

        $umkms->getCollection()->transform(function (Umkm $umkm) use ($hotels) {
            $umkm->nearest_hotel = $this->nearestHotel($umkm, $hotels);
            return $umkm;
        });

        return UmkmPopularResource::collection($umkms)->response();
    }

    private function nearestHotel(Umkm $umkm, $hotels): ?array
    {
        if ($hotels->isEmpty()) return null;

        $uLat = (float) $umkm->latitude;
        $uLng = (float) $umkm->longitude;

        $best = null;
        $bestDist = PHP_FLOAT_MAX;

        foreach ($hotels as $hotel) {
            $dist = $this->haversineKm($uLat, $uLng, (float) $hotel->latitude, (float) $hotel->longitude);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $hotel;
            }
        }

        if (!$best) return null;

        $minPrice = $best->rooms?->where('status', 'available')->min('price_per_night') ?? 0;

        return [
            'id'           => $best->id,
            'name'         => $best->name,
            'slug'         => $best->slug,
            'thumbnail'    => $best->thumbnail,
            'star_rating'  => $best->star_rating,
            'min_price'    => (float) $minPrice,
            'distance_km'  => round($bestDist, 1),
        ];
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function show(string $slug): JsonResponse
    {
        $umkm = Umkm::with(['category', 'destination', 'products', 'reviews'])
            ->where('slug', $slug)
            ->where('status', UmkmStatus::ACTIVE)
            ->firstOrFail();

        return response()->json(['data' => new UmkmResource($umkm)]);
    }
}