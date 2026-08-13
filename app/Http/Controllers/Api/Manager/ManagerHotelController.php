<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelGallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManagerHotelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $hotels = Hotel::with(['destination', 'rooms'])
            ->where('manager_id', auth()->id())
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('address', 'like', "%{$request->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($hotels);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'thumbnail' => 'nullable|string|max:500',
            'destination_id' => 'nullable|exists:destinations,id',
            'status' => 'nullable|in:published,draft,archived',
        ]);

        $validated['slug'] = Hotel::generateUniqueSlug($validated['name']);
        $validated['manager_id'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'draft';

        $hotel = Hotel::create($validated);

        return response()->json([
            'message' => 'Hotel berhasil dibuat.',
            'data' => $hotel->load('destination'),
        ], 201);
    }

    public function show(Hotel $hotel): JsonResponse
    {
        $this->verifyOwnership($hotel);

        $hotel->load([
            'destination',
            'rooms',
            'galleries' => fn($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $hotel]);
    }

    public function update(Request $request, Hotel $hotel): JsonResponse
    {
        $this->verifyOwnership($hotel);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'address' => 'sometimes|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'thumbnail' => 'nullable|string|max:500',
            'destination_id' => 'nullable|exists:destinations,id',
            'status' => 'sometimes|in:published,draft,archived',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $hotel->name) {
            $validated['slug'] = Hotel::generateUniqueSlug($validated['name']);
        }

        $hotel->update($validated);

        return response()->json([
            'message' => 'Hotel berhasil diupdate.',
            'data' => $hotel->load('destination'),
        ]);
    }

    public function destroy(Hotel $hotel): JsonResponse
    {
        $this->verifyOwnership($hotel);
        $hotel->delete();

        return response()->json(['message' => 'Hotel berhasil dihapus.']);
    }

    public function storeGallery(Request $request, Hotel $hotel): JsonResponse
    {
        $this->verifyOwnership($hotel);

        $validated = $request->validate([
            'image' => 'required|string|max:500',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $gallery = $hotel->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(Hotel $hotel, HotelGallery $gallery): JsonResponse
    {
        $this->verifyOwnership($hotel);

        if ($gallery->hotel_id !== $hotel->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di hotel ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }

    private function verifyOwnership(Hotel $hotel): void
    {
        if ($hotel->manager_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke hotel ini.');
        }
    }
}