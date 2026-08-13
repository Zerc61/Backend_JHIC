<?php

namespace App\Http\Controllers\Api\Umkm;

use App\Http\Controllers\Controller;
use App\Http\Resources\UmkmResource;
use App\Models\Umkm;
use App\Enums\UmkmStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UmkmProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $umkm = Umkm::with(['category', 'destination'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$umkm) {
            return response()->json(['data' => null], 200);
        }

        return response()->json(['data' => new UmkmResource($umkm)]);
    }

    public function store(Request $request): JsonResponse
    {
        if (Umkm::where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'message' => 'Anda sudah memiliki UMKM. Gunakan endpoint update.',
            ], 422);
        }

        $validated = $request->validate([
            'destination_id' => 'required|exists:destinations,id',
            'umkm_category_id' => 'required|exists:umkm_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'required|string|max:30',
            'opening_hours' => 'nullable|string|max:100',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['slug'] = Umkm::generateUniqueSlug($validated['name']);
        $validated['status'] = UmkmStatus::PENDING->value;

        $umkm = Umkm::create($validated);

        return response()->json([
            'message' => 'Profil UMKM berhasil dibuat. Menunggu persetujuan admin.',
            'data' => new UmkmResource($umkm->load(['category', 'destination'])),
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $umkm = Umkm::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'umkm_category_id' => 'sometimes|exists:umkm_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'sometimes|string|max:30',
            'opening_hours' => 'nullable|string|max:100',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $umkm->name) {
            $validated['slug'] = Umkm::generateUniqueSlug($validated['name']);
        }

        $umkm->update($validated);

        return response()->json([
            'message' => 'Profil UMKM berhasil diupdate.',
            'data' => new UmkmResource($umkm->load(['category', 'destination'])),
        ]);
    }
}
