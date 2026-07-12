<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransportationResource;
use App\Models\Transportation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transports = Transportation::query()
            ->published()
            ->with('destination')
            ->when($request->destination_id, fn ($q, $id) => $q->where('destination_id', $id))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->includes_driver, fn ($q) => $q->where('includes_driver', true))
            ->latest()
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'data' => TransportationResource::collection($transports),
            'meta' => [
                'current_page' => $transports->currentPage(),
                'last_page'    => $transports->lastPage(),
                'per_page'     => $transports->perPage(),
                'total'        => $transports->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $transport = Transportation::query()
            ->published()
            ->with(['destination', 'galleries', 'reviews', 'wishlists'])
            ->where('slug', $slug)
            ->firstOrFail();

        if (auth()->check()) {
            $transport->load(['wishlists' => fn ($q) => $q->where('user_id', auth()->id())]);
        }

        return response()->json([
            'data' => new TransportationResource($transport),
        ]);
    }
}