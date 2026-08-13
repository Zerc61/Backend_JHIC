<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFacilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $facilities = Facility::query()
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return response()->json($facilities);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $facility = Facility::create($validated);

        return response()->json(['message' => 'Fasilitas berhasil dibuat.', 'data' => $facility], 201);
    }

    public function show(Facility $facility): JsonResponse
    {
        return response()->json(['data' => $facility]);
    }

    public function update(Request $request, Facility $facility): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $facility->update($validated);

        return response()->json(['message' => 'Fasilitas berhasil diupdate.', 'data' => $facility]);
    }

    public function destroy(Facility $facility): JsonResponse
    {
        $facility->delete();

        return response()->json(['message' => 'Fasilitas berhasil dihapus.']);
    }
}