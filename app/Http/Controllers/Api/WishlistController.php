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
        $wishlists = Wishlist::with('destination.category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => WishlistResource::collection($wishlists)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['destination_id' => 'required|exists:destinations,id']);

        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'destination_id' => $request->destination_id,
        ]);

        return response()->json(['message' => 'Ditambahkan ke wishlist', 'data' => new WishlistResource($wishlist)]);
    }

    public function destroy(Destination $destination, Request $request): JsonResponse
    {
        Wishlist::where('user_id', $request->user()->id)
            ->where('destination_id', $destination->id)
            ->delete();

        return response()->json(['message' => 'Dihapus dari wishlist']);
    }

    public function check(Destination $destination): JsonResponse
{
    $wishlist = Wishlist::where('user_id', auth()->id())
        ->where('destination_id', $destination->id)
        ->first();

    return response()->json([
        'is_wishlisted' => $wishlist !== null,
    ]);
}
}