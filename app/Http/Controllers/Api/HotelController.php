<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $hotels = Hotel::query()
            ->published()
            ->with('destination')
            ->when($request->destination_id, fn ($q, $id) => $q->where('destination_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->star_rating, fn ($q, $r) => $q->where('star_rating', '>=', (int) $r))
            ->latest()
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'data' => HotelResource::collection($hotels),
            'meta' => [
                'current_page'  => $hotels->currentPage(),
                'last_page'     => $hotels->lastPage(),
                'per_page'      => $hotels->perPage(),
                'total'         => $hotels->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $hotel = Hotel::query()
            ->published()
            ->with(['destination', 'rooms', 'galleries', 'reviews', 'wishlists'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Filter wishlists milik user yang login
        if (auth()->check()) {
            $hotel->load(['wishlists' => fn ($q) => $q->where('user_id', auth()->id())]);
        }

        return response()->json([
            'data' => new HotelResource($hotel),
        ]);
    }
}