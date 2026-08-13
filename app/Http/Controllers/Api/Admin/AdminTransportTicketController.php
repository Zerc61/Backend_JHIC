<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportTicket;
use App\Enums\TransportMode;
use App\Enums\TransportTicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class AdminTransportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TransportTicket::query();

        if ($request->filled('transport_mode')) {
            $query->where('transport_mode', $request->transport_mode);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('origin_code')) {
            $query->where('origin_code', $request->origin_code);
        }

        if ($request->filled('destination_code')) {
            $query->where('destination_code', $request->destination_code);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('provider', 'like', "%{$search}%")
                  ->orWhere('origin_name', 'like', "%{$search}%")
                  ->orWhere('destination_name', 'like', "%{$search}%")
                  ->orWhere('flight_number', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('departure_time', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|max:255',
            'transport_mode' => ['required', new Enum(TransportMode::class)],
            'origin_code' => 'required|string|max:10',
            'origin_name' => 'required|string|max:255',
            'destination_code' => 'required|string|max:10',
            'destination_name' => 'required|string|max:255',
            'flight_number' => 'nullable|string|max:20',
            'departure_time' => 'required|date',
            'arrival_time' => 'required|date|after:departure_time',
            'duration_minutes' => 'required|integer|min:1',
            'is_transit' => 'nullable|boolean',
            'transit_info' => 'nullable|string|max:500',
            'class_type' => 'nullable|string|max:100',
            'available_seats' => 'required|integer|min:0',
            'price_per_ticket' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date',
            'raw_response' => 'nullable|array',
        ]);

        $validated['status'] = TransportTicketStatus::AVAILABLE->value;

        $ticket = TransportTicket::create($validated);

        return response()->json(['message' => 'Tiket transportasi berhasil ditambahkan.', 'data' => $ticket], 201);
    }

    public function show(TransportTicket $transportTicket): JsonResponse
    {
        $transportTicket->loadCount('bookings');

        return response()->json(['data' => $transportTicket]);
    }

    public function update(Request $request, TransportTicket $transportTicket): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'sometimes|string|max:255',
            'transport_mode' => ['sometimes', new Enum(TransportMode::class)],
            'origin_code' => 'sometimes|string|max:10',
            'origin_name' => 'sometimes|string|max:255',
            'destination_code' => 'sometimes|string|max:10',
            'destination_name' => 'sometimes|string|max:255',
            'flight_number' => 'nullable|string|max:20',
            'departure_time' => 'sometimes|date',
            'arrival_time' => 'sometimes|date|after:departure_time',
            'duration_minutes' => 'sometimes|integer|min:1',
            'is_transit' => 'nullable|boolean',
            'transit_info' => 'nullable|string|max:500',
            'class_type' => 'nullable|string|max:100',
            'available_seats' => 'sometimes|integer|min:0',
            'price_per_ticket' => 'sometimes|numeric|min:0',
            'status' => ['sometimes', new Enum(TransportTicketStatus::class)],
            'valid_until' => 'nullable|date',
            'raw_response' => 'nullable|array',
        ]);

        $transportTicket->update($validated);

        return response()->json(['message' => 'Tiket transportasi berhasil diupdate.', 'data' => $transportTicket]);
    }

    public function destroy(TransportTicket $transportTicket): JsonResponse
    {
        $transportTicket->delete();

        return response()->json(['message' => 'Tiket transportasi berhasil dihapus.']);
    }
}