<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\DestinationGallery;
use App\Models\DestinationCategory;
use App\Models\User;
use App\Enums\DestinationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;

class AdminDestinationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Destination::with(['category', 'manager']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('destination_category_id', $request->category_id);
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $destinations = $query->orderBy('created_at', 'desc')
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
        $validated['status'] = $validated['status'] ?? DestinationStatus::DRAFT->value;

        $destination = Destination::create($validated);

        return response()->json([
            'message' => 'Destination berhasil dibuat.',
            'data' => $destination->load('category', 'manager'),
        ], 201);
    }

    public function show(Destination $destination): JsonResponse
    {
        $destination->load(['category', 'manager', 'facilities', 'galleries' => fn($q) => $q->orderBy('sort_order')]);

        return response()->json(['data' => $destination]);
    }

    public function update(Request $request, Destination $destination): JsonResponse
    {
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
            'status' => ['sometimes', new Enum(DestinationStatus::class)],
        ]);

        if (isset($validated['name']) && $validated['name'] !== $destination->name) {
            $validated['slug'] = Destination::generateUniqueSlug($validated['name']);
        }

        $destination->update($validated);

        return response()->json([
            'message' => 'Destination berhasil diupdate.',
            'data' => $destination->load('category', 'manager'),
        ]);
    }

    public function destroy(Destination $destination): JsonResponse
    {
        $destination->delete();

        return response()->json(['message' => 'Destination berhasil dihapus.']);
    }

    public function assignManager(Request $request, Destination $destination): JsonResponse
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:users,id',
        ]);

        $manager = User::find($validated['manager_id']);

        if (!$manager->isManager()) {
            return response()->json(['message' => 'User ini bukan manager.'], 422);
        }

        $destination->update(['manager_id' => $validated['manager_id']]);

        return response()->json([
            'message' => 'Manager berhasil ditetapkan.',
            'data' => $destination->load('manager'),
        ]);
    }

    public function storeGallery(Request $request, Destination $destination): JsonResponse
    {
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

    public function destroyGallery(Destination $destination, DestinationGallery $gallery): JsonResponse
    {
        if ($gallery->destination_id !== $destination->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di destination ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }
}