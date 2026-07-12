<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TravelPackageResource;
use App\Models\TravelPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelPackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $packages = TravelPackage::query()
            ->published()
            ->with(['destination', 'schedules'])
            ->when($request->destination_id, fn ($q, $id) => $q->where('destination_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->min_price, fn ($q, $p) => $q->where('price_per_person', '>=', $p))
            ->when($request->max_price, fn ($q, $p) => $q->where('price_per_person', '<=', $p))
            ->latest()
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'data' => TravelPackageResource::collection($packages),
            'meta' => [
                'current_page' => $packages->currentPage(),
                'last_page'    => $packages->lastPage(),
                'per_page'     => $packages->perPage(),
                'total'        => $packages->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $package = TravelPackage::query()
            ->published()
            ->with(['destination', 'hotel', 'transportation', 'galleries', 'schedules', 'wishlists'])
            ->where('slug', $slug)
            ->firstOrFail();

        if (auth()->check()) {
            $package->load(['wishlists' => fn ($q) => $q->where('user_id', auth()->id())]);
        }

        return response()->json([
            'data' => new TravelPackageResource($package),
        ]);
    }
}