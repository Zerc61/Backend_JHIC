<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DestinationCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = DestinationCategory::query()
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:destination_categories,slug',
            'icon' => 'nullable|string|max:255',
        ]);

        $category = DestinationCategory::create($validated);

        return response()->json(['message' => 'Kategori berhasil dibuat.', 'data' => $category], 201);
    }

    public function show(DestinationCategory $destinationCategory): JsonResponse
    {
        $destinationCategory->loadCount('destinations');

        return response()->json(['data' => $destinationCategory]);
    }

    public function update(Request $request, DestinationCategory $destinationCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => "sometimes|string|max:255|unique:destination_categories,slug,{$destinationCategory->id}",
            'icon' => 'nullable|string|max:255',
        ]);

        $destinationCategory->update($validated);

        return response()->json(['message' => 'Kategori berhasil diupdate.', 'data' => $destinationCategory]);
    }

    public function destroy(DestinationCategory $destinationCategory): JsonResponse
    {
        if ($destinationCategory->destinations()->exists()) {
            return response()->json(['message' => 'Kategori masih memiliki destination. Hapus atau pindahkan dulu.'], 409);
        }

        $destinationCategory->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}