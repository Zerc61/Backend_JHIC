<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminHotelRoomController extends Controller
{
    public function index(Hotel $hotel): JsonResponse
    {
        $rooms = $hotel->rooms()->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $rooms]);
    }

    public function store(Request $request, Hotel $hotel): JsonResponse
    {
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

    public function show(Hotel $hotel, HotelRoom $room): JsonResponse
    {
        return response()->json(['data' => $room]);
    }

    public function update(Request $request, Hotel $hotel, HotelRoom $room): JsonResponse
    {
        if ($room->hotel_id !== $hotel->id) {
            return response()->json(['message' => 'Kamar tidak ditemukan di hotel ini.'], 404);
        }

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

    public function destroy(Hotel $hotel, HotelRoom $room): JsonResponse
    {
        if ($room->hotel_id !== $hotel->id) {
            return response()->json(['message' => 'Kamar tidak ditemukan di hotel ini.'], 404);
        }

        $room->delete();

        return response()->json(['message' => 'Kamar berhasil dihapus.']);
    }
}