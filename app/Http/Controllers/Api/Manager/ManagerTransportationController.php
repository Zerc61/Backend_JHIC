<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Transportation;
use App\Models\TransportationGallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerTransportationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transports = Transportation::with(['destination', 'galleries'])
            ->where('manager_id', auth()->id())
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($transports);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'destination_id' => 'nullable|exists:destinations,id',
            'type' => 'nullable|in:car,motorcycle,bus,boat,other',
            'description' => 'required|string',
            'capacity' => 'nullable|integer|min:1',
            'price_per_day' => 'required|numeric|min:0',
            'includes_driver' => 'nullable|boolean',
            'includes_fuel' => 'nullable|boolean',
            'thumbnail' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'status' => 'nullable|in:published,draft,archived',
        ]);

        $validated['slug'] = Transportation::generateUniqueSlug($validated['name']);
        $validated['manager_id'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'draft';

        $transport = Transportation::create($validated);

        return response()->json([
            'message' => 'Transportasi berhasil dibuat.',
            'data' => $transport->load('destination'),
        ], 201);
    }

    public function show(Transportation $transportation): JsonResponse
    {
        $this->verifyOwnership($transportation);

        $transportation->load([
            'destination',
            'galleries' => fn($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $transportation]);
    }

    public function update(Request $request, Transportation $transportation): JsonResponse
    {
        $this->verifyOwnership($transportation);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'destination_id' => 'nullable|exists:destinations,id',
            'type' => 'sometimes|in:car,motorcycle,bus,boat,other',
            'description' => 'sometimes|required|string',
            'capacity' => 'nullable|integer|min:1',
            'price_per_day' => 'sometimes|numeric|min:0',
            'includes_driver' => 'nullable|boolean',
            'includes_fuel' => 'nullable|boolean',
            'thumbnail' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'status' => 'sometimes|in:published,draft,archived',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $transportation->name) {
            $validated['slug'] = Transportation::generateUniqueSlug($validated['name']);
        }

        $transportation->update($validated);

        return response()->json([
            'message' => 'Transportasi berhasil diupdate.',
            'data' => $transportation->load('destination'),
        ]);
    }

    public function destroy(Transportation $transportation): JsonResponse
    {
        $this->verifyOwnership($transportation);
        $transportation->delete();

        return response()->json(['message' => 'Transportasi berhasil dihapus.']);
    }

    public function storeGallery(Request $request, Transportation $transportation): JsonResponse
    {
        $this->verifyOwnership($transportation);

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('transportation-galleries', 'public');
        $validated['image'] = $path;

        $gallery = $transportation->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(Transportation $transportation, TransportationGallery $gallery): JsonResponse
    {
        $this->verifyOwnership($transportation);

        if ($gallery->transportation_id !== $transportation->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di transportasi ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }

    private function verifyOwnership(Transportation $transportation): void
    {
        if ($transportation->manager_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke transportasi ini.');
        }
    }
}
