<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => 'required|in:Destination,Umkm,Product',
            'reviewable_id' => 'required|integer',
        ]);

        $reviews = Review::with('user')
            ->where('reviewable_type', "App\\Models\\{$request->reviewable_type}")
            ->where('reviewable_id', $request->reviewable_id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => 'required|in:Destination,Umkm,Product',
            'reviewable_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::create([
            'user_id' => $request->user()->id,
            'reviewable_type' => "App\\Models\\{$request->reviewable_type}",
            'reviewable_id' => $request->reviewable_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json(['message' => 'Review berhasil ditambahkan', 'data' => new ReviewResource($review->load('user'))], 201);
    }
}