<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelPackage;
use App\Models\TravelPackageGallery;
use App\Models\TravelPackageSchedule;
use App\Models\User;
use App\Enums\TravelPackageStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;

class AdminTravelPackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TravelPackage::with(['manager', 'destination', 'hotel', 'schedules']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $packages = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($packages);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'nullable|string|max:500',
            'destination_id' => 'nullable|exists:destinations,id',
            'hotel_id' => 'nullable|exists:hotels,id',
            'manager_id' => 'nullable|exists:users,id',
            'duration_days' => 'nullable|integer|min:1',
            'duration_nights' => 'nullable|integer|min:0',
            'price_per_person' => 'required|numeric|min:0',
            'max_travelers' => 'nullable|integer|min:1',
            'included_items' => 'nullable|array',
            'included_items.*' => 'string',
            'excluded_items' => 'nullable|array',
            'excluded_items.*' => 'string',
            'meals_included' => 'nullable|array',
            'benefits' => 'nullable|array',
            'terms_conditions' => 'nullable|string',
            'status' => ['nullable', new Enum(TravelPackageStatus::class)],
        ]);

        $validated['slug'] = TravelPackage::generateUniqueSlug($validated['name']);
        $validated['status'] = $validated['status'] ?? TravelPackageStatus::DRAFT->value;

        $package = TravelPackage::create($validated);

        return response()->json([
            'message' => 'Travel package berhasil dibuat.',
            'data' => $package->load('manager', 'destination', 'hotel'),
        ], 201);
    }

    public function show(TravelPackage $package): JsonResponse
    {
        $package->load([
            'manager',
            'destination',
            'hotel',
            'schedules' => fn($q) => $q->orderBy('departure_date'),
            'galleries' => fn($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $package]);
    }

    public function update(Request $request, TravelPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'thumbnail' => 'nullable|string|max:500',
            'destination_id' => 'nullable|exists:destinations,id',
            'hotel_id' => 'nullable|exists:hotels,id',
            'manager_id' => 'nullable|exists:users,id',
            'duration_days' => 'nullable|integer|min:1',
            'duration_nights' => 'nullable|integer|min:0',
            'price_per_person' => 'sometimes|numeric|min:0',
            'max_travelers' => 'nullable|integer|min:1',
            'included_items' => 'nullable|array',
            'included_items.*' => 'string',
            'excluded_items' => 'nullable|array',
            'excluded_items.*' => 'string',
            'meals_included' => 'nullable|array',
            'benefits' => 'nullable|array',
            'terms_conditions' => 'nullable|string',
            'status' => ['sometimes', new Enum(TravelPackageStatus::class)],
        ]);

        if (isset($validated['name']) && $validated['name'] !== $package->name) {
            $validated['slug'] = TravelPackage::generateUniqueSlug($validated['name']);
        }

        $package->update($validated);

        return response()->json([
            'message' => 'Travel package berhasil diupdate.',
            'data' => $package->load('manager', 'destination', 'hotel'),
        ]);
    }

    public function destroy(TravelPackage $package): JsonResponse
    {
        $package->delete();

        return response()->json(['message' => 'Travel package berhasil dihapus.']);
    }

    public function assignManager(Request $request, TravelPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:users,id',
        ]);

        $manager = User::find($validated['manager_id']);

        if (!$manager->isManager()) {
            return response()->json(['message' => 'User ini bukan manager.'], 422);
        }

        $package->update(['manager_id' => $validated['manager_id']]);

        return response()->json([
            'message' => 'Manager berhasil ditetapkan.',
            'data' => $package->load('manager'),
        ]);
    }

    public function storeGallery(Request $request, TravelPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('travel-package-galleries', 'public');
        $validated['image'] = $path;

        $gallery = $package->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(TravelPackage $package, TravelPackageGallery $gallery): JsonResponse
    {
        if ($gallery->travel_package_id !== $package->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di package ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }

    // ── Schedules ──

    public function scheduleStore(Request $request, TravelPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'departure_date' => 'required|date|after:today',
            'return_date' => 'required|date|after:departure_date',
            'max_capacity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'pickup_location' => 'nullable|string|max:500',
            'pickup_time' => 'nullable|date_format:H:i',
            'vehicle_info' => 'nullable|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:20',
        ]);

        $schedule = $package->schedules()->create($validated);

        return response()->json(['message' => 'Jadwal berhasil ditambahkan.', 'data' => $schedule], 201);
    }

    public function scheduleUpdate(Request $request, TravelPackage $package, TravelPackageSchedule $schedule): JsonResponse
    {
        if ($schedule->travel_package_id !== $package->id) {
            return response()->json(['message' => 'Jadwal tidak ditemukan di package ini.'], 404);
        }

        $validated = $request->validate([
            'departure_date' => 'sometimes|date',
            'return_date' => 'sometimes|date|after:departure_date',
            'max_capacity' => 'sometimes|integer|min:1',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:available,full,cancelled',
            'pickup_location' => 'nullable|string|max:500',
            'pickup_time' => 'nullable|date_format:H:i',
            'vehicle_info' => 'nullable|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:20',
        ]);

        $schedule->update($validated);

        return response()->json(['message' => 'Jadwal berhasil diupdate.', 'data' => $schedule]);
    }

    public function scheduleDestroy(TravelPackage $package, TravelPackageSchedule $schedule): JsonResponse
    {
        if ($schedule->travel_package_id !== $package->id) {
            return response()->json(['message' => 'Jadwal tidak ditemukan di package ini.'], 404);
        }

        $schedule->delete();

        return response()->json(['message' => 'Jadwal berhasil dihapus.']);
    }
}