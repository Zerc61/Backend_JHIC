<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\DestinationGallery;
use App\Models\Facility;
use App\Enums\DestinationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;

class ManagerDestinationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $destinations = Destination::with(['category', 'facilities'])
            ->where('manager_id', auth()->id())
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('address', 'like', "%{$request->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($destinations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_category_id' => 'required|exists:destination_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'open_hour' => 'nullable|date_format:H:i',
            'close_hour' => 'nullable|date_format:H:i',
            'ticket_price' => 'nullable|numeric|min:0',
            'estimated_cost' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'status' => ['nullable', new Enum(DestinationStatus::class)],
        ]);

        $validated['slug'] = Destination::generateUniqueSlug($validated['name']);
        $validated['manager_id'] = auth()->id();
        $validated['status'] = $validated['status'] ?? DestinationStatus::DRAFT->value;

        $destination = Destination::create($validated);

        return response()->json([
            'message' => 'Destination berhasil dibuat.',
            'data' => $destination->load('category'),
        ], 201);
    }

    public function storeGallery(Request $request, Destination $destination): JsonResponse
    {
        $this->verifyOwnership($destination);

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('destination-galleries', 'public');
        $validated['image'] = $path;

        $gallery = $destination->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function show(Destination $destination): JsonResponse
    {
        $this->verifyOwnership($destination);

        $destination->load([
            'category',
            'facilities',
            'galleries' => fn($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $destination]);
    }

    public function update(Request $request, Destination $destination): JsonResponse
    {
        $this->verifyOwnership($destination);

        $validated = $request->validate([
            'destination_category_id' => 'sometimes|exists:destination_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'address' => 'sometimes|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'open_hour' => 'nullable|date_format:H:i',
            'close_hour' => 'nullable|date_format:H:i',
            'ticket_price' => 'nullable|numeric|min:0',
            'estimated_cost' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'status' => 'sometimes|in:published,draft,archived',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $destination->name) {
            $validated['slug'] = Destination::generateUniqueSlug($validated['name']);
        }

        $destination->update($validated);

        return response()->json([
            'message' => 'Destination berhasil diupdate.',
            'data' => $destination->load('category'),
        ]);
    }

    public function destroy(Destination $destination): JsonResponse
    {
        $this->verifyOwnership($destination);
        $destination->delete();

        return response()->json(['message' => 'Destination berhasil dihapus.']);
    }

    public function destroyGallery(Destination $destination, DestinationGallery $gallery): JsonResponse
    {
        $this->verifyOwnership($destination);

        if ($gallery->destination_id !== $destination->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di destination ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }

    public function syncFacilities(Request $request, Destination $destination): JsonResponse
    {
        $this->verifyOwnership($destination);

        $validated = $request->validate([
            'facility_ids' => 'required|array',
            'facility_ids.*' => 'exists:facilities,id',
        ]);

        $destination->facilities()->sync($validated['facility_ids']);

        return response()->json([
            'message' => 'Fasilitas berhasil diperbarui.',
            'data' => $destination->load('facilities'),
        ]);
    }

    private function verifyOwnership(Destination $destination): void
    {
        if ($destination->manager_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke destination ini.');
        }
    }
}