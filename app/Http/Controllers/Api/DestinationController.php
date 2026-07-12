<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DestinationDetailResource;
use App\Http\Resources\DestinationResource;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Enums\DestinationStatus; 
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function index(Request $request): JsonResponse
{
    $destinations = Destination::with(['category', 'galleries'])  
        ->where('status', DestinationStatus::PUBLISHED)
        ->when($request->category, fn($q, $cat) => $q->whereHas('category', fn($q2) => $q2->where('slug', $cat)))
        ->when($request->search, fn($q, $search) => $q->where('name', 'like', "%{$search}%"))
        ->latest()
        ->paginate($request->per_page ?? 12);

    return response()->json([
        'data' => DestinationResource::collection($destinations->items()),
        'meta' => [
            'current_page' => $destinations->currentPage(),
            'last_page' => $destinations->lastPage(),
            'per_page' => $destinations->perPage(),
            'total' => $destinations->total(),
        ],
    ]);
}

    public function show(string $slug): JsonResponse
    {
        $destination = Destination::with(['category', 'galleries', 'facilities', 'umkms', 'events'])
            ->where('slug', $slug)
            ->where('status', DestinationStatus::PUBLISHED) // <-- DIPERBAIKI
            ->firstOrFail();

        return response()->json([
            'data' => new DestinationDetailResource($destination),
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = DestinationCategory::withCount(['destinations' => fn($q) => $q->where('status', DestinationStatus::PUBLISHED)]) // <-- DIPERBAIKI
            ->get();

        return response()->json(['data' => $categories]);
    }
}