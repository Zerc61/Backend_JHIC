<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelGallery;
use App\Models\User;
use App\Enums\HotelStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;

class AdminHotelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Hotel::with(['manager', 'destination', 'rooms']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->filled('destination_id')) {
            $query->where('destination_id', $request->destination_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $hotels = $query->orderBy('created_at', 'desc')
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
            'manager_id' => 'nullable|exists:users,id',
            'status' => ['nullable', new Enum(HotelStatus::class)],
        ]);

        // Generate slug unik (gunakan method di model Hotel)
        $validated['slug'] = Hotel::generateUniqueSlug($validated['name']);
        $validated['status'] = $validated['status'] ?? HotelStatus::DRAFT->value;

        $hotel = Hotel::create($validated);

        return response()->json([
            'message' => 'Hotel berhasil dibuat.',
            'data' => $hotel->load('manager', 'destination'),
        ], 201);
    }

    public function show(Hotel $hotel): JsonResponse
    {
        $hotel->load([
            'manager',
            'destination',
            'rooms',
            'galleries' => fn($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $hotel]);
    }

    public function update(Request $request, Hotel $hotel): JsonResponse
    {
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
            'manager_id' => 'nullable|exists:users,id',
            'status' => ['sometimes', new Enum(HotelStatus::class)],
        ]);

        if (isset($validated['name']) && $validated['name'] !== $hotel->name) {
            $validated['slug'] = Hotel::generateUniqueSlug($validated['name']);
        }

        $hotel->update($validated);

        return response()->json([
            'message' => 'Hotel berhasil diupdate.',
            'data' => $hotel->load('manager', 'destination'),
        ]);
    }

    public function destroy(Hotel $hotel): JsonResponse
    {
        $hotel->delete();

        return response()->json(['message' => 'Hotel berhasil dihapus.']);
    }

    public function assignManager(Request $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:users,id',
        ]);

        $manager = User::find($validated['manager_id']);

        if (!$manager->isManager()) {
            return response()->json(['message' => 'User ini bukan manager.'], 422);
        }

        $hotel->update(['manager_id' => $validated['manager_id']]);

        return response()->json([
            'message' => 'Manager berhasil ditetapkan.',
            'data' => $hotel->load('manager'),
        ]);
    }

    public function storeGallery(Request $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('hotel-galleries', 'public');
        $validated['image'] = $path;

        $gallery = $hotel->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(Hotel $hotel, HotelGallery $gallery): JsonResponse
    {
        if ($gallery->hotel_id !== $hotel->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di hotel ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }
}