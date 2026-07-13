<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Wishlist;
use App\Models\Destination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = Wishlist::with('wishlistable')
            ->where('user_id', $request->user()->id)
            ->where('wishlistable_type', Destination::class)
            ->latest()
            ->get();

        return response()->json(['data' => WishlistResource::collection($wishlists)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['destination_id' => 'required|exists:destinations,id']);

        $wishlist = Wishlist::firstOrCreate([
            'user_id'              => $request->user()->id,
            'wishlistable_id'      => $request->destination_id,
            'wishlistable_type'    => Destination::class,
        ]);

        return response()->json(['message' => 'Ditambahkan ke wishlist', 'data' => new WishlistResource($wishlist)]);
    }

    public function destroy(Request $request, int $destinationId): JsonResponse
    {
        $deleted = Wishlist::where('user_id', $request->user()->id)
            ->where('wishlistable_id', $destinationId)
            ->where('wishlistable_type', Destination::class)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Wishlist tidak ditemukan'], 404);
        }

        return response()->json(['message' => 'Dihapus dari wishlist']);
    }

    public function check(Request $request, int $destinationId): JsonResponse
    {
        $exists = Wishlist::where('user_id', $request->user()->id)
            ->where('wishlistable_id', $destinationId)
            ->where('wishlistable_type', Destination::class)
            ->exists();

        return response()->json([
            'is_wishlisted' => $exists,
        ]);
    }
}