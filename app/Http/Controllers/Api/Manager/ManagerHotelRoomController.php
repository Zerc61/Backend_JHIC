<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerHotelRoomController extends Controller
{
    public function store(Request $request, Hotel $hotel): JsonResponse
    {
        $this->verifyHotelOwnership($hotel);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'total_rooms' => 'nullable|integer|min:1',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'status' => 'nullable|in:available,unavailable',
        ]);

        $validated['status'] = $validated['status'] ?? 'available';

        $room = $hotel->rooms()->create($validated);

        return response()->json(['message' => 'Kamar berhasil ditambahkan.', 'data' => $room], 201);
    }

    public function update(Request $request, Hotel $hotel, HotelRoom $room): JsonResponse
    {
        $this->verifyHotelOwnership($hotel);
        $this->verifyRoomBelongsToHotel($room, $hotel);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'price_per_night' => 'sometimes|numeric|min:0',
            'total_rooms' => 'nullable|integer|min:1',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'status' => 'sometimes|in:available,unavailable',
        ]);

        $room->update($validated);

        return response()->json(['message' => 'Kamar berhasil diupdate.', 'data' => $room]);
    }

    public function destroy(Request $request, Hotel $hotel, HotelRoom $room): JsonResponse
    {
        $this->verifyHotelOwnership($hotel);
        $this->verifyRoomBelongsToHotel($room, $hotel);

        $room->delete();

        return response()->json(['message' => 'Kamar berhasil dihapus.']);
    }

    private function verifyHotelOwnership(Hotel $hotel): void
    {
        if ($hotel->manager_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke hotel ini.');
        }
    }

    private function verifyRoomBelongsToHotel(HotelRoom $room, Hotel $hotel): void
    {
        if ($room->hotel_id !== $hotel->id) {
            abort(404, 'Kamar tidak ditemukan di hotel ini.');
        }
    }
}