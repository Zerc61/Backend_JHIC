<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UmkmResource;
use App\Models\Umkm;
use App\Enums\UmkmStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UmkmController extends Controller
{
    public function byDestination(string $destinationSlug, Request $request): JsonResponse
    {
        $umkms = Umkm::with(['category', 'destination'])
            ->whereHas('destination', fn($q) => $q->where('slug', $destinationSlug)->where('status', \App\Enums\DestinationStatus::PUBLISHED))
            ->where('status', UmkmStatus::ACTIVE)
            ->when($request->category, fn($q, $cat) => $q->whereHas('category', fn($q2) => $q2->where('slug', $cat)))
            ->get();

        return response()->json(['data' => UmkmResource::collection($umkms)]);
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