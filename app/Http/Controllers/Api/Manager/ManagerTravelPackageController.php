<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\TravelPackage;
use App\Models\TravelPackageGallery;
use App\Models\TravelPackageSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManagerTravelPackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $packages = TravelPackage::with(['destination', 'hotel', 'schedules'])
            ->where('manager_id', auth()->id())
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('created_at', 'desc')
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
            'status' => 'nullable|in:published,draft,archived',
        ]);

        $validated['slug'] = TravelPackage::generateUniqueSlug($validated['name']);
        $validated['manager_id'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'draft';

        $package = TravelPackage::create($validated);

        return response()->json([
            'message' => 'Travel package berhasil dibuat.',
            'data' => $package->load('destination', 'hotel'),
        ], 201);
    }

    public function show(TravelPackage $package): JsonResponse
    {
        $this->verifyOwnership($package);

        $package->load([
            'destination',
            'hotel',
            'schedules' => fn($q) => $q->orderBy('departure_date'),
            'galleries' => fn($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $package]);
    }

    public function update(Request $request, TravelPackage $package): JsonResponse
    {
        $this->verifyOwnership($package);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'thumbnail' => 'nullable|string|max:500',
            'destination_id' => 'nullable|exists:destinations,id',
            'hotel_id' => 'nullable|exists:hotels,id',
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
            'status' => 'sometimes|in:published,draft,archived',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $package->name) {
            $validated['slug'] = TravelPackage::generateUniqueSlug($validated['name']);
        }

        $package->update($validated);

        return response()->json([
            'message' => 'Travel package berhasil diupdate.',
            'data' => $package->load('destination', 'hotel'),
        ]);
    }

    public function destroy(TravelPackage $package): JsonResponse
    {
        $this->verifyOwnership($package);
        $package->delete();

        return response()->json(['message' => 'Travel package berhasil dihapus.']);
    }

    public function storeGallery(Request $request, TravelPackage $package): JsonResponse
    {
        $this->verifyOwnership($package);

        $validated = $request->validate([
            'image' => 'required|string|max:500',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $gallery = $package->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(TravelPackage $package, TravelPackageGallery $gallery): JsonResponse
    {
        $this->verifyOwnership($package);

        if ($gallery->travel_package_id !== $package->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di package ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }

    // ── Schedules ──

    public function scheduleStore(Request $request, TravelPackage $package): JsonResponse
    {
        $this->verifyOwnership($package);

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
        $this->verifyOwnership($package);

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
        $this->verifyOwnership($package);

        if ($schedule->travel_package_id !== $package->id) {
            return response()->json(['message' => 'Jadwal tidak ditemukan di package ini.'], 404);
        }

        $schedule->delete();

        return response()->json(['message' => 'Jadwal berhasil dihapus.']);
    }

    private function verifyOwnership(TravelPackage $package): void
    {
        if ($package->manager_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke travel package ini.');
        }
    }
}