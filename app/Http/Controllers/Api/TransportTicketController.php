<?php

// app/Http/Controllers/Api/TransportTicketController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransportTicketDetailResource;
use App\Http\Resources\TransportTicketResource;
use App\Services\TransportTicket\TransportTicketServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportTicketController extends Controller
{
    public function __construct(
        private TransportTicketServiceInterface $ticketService
    ) {}

    /**
     * Cari tiket tersedia
     * 
     * GET /api/transport-tickets/search?origin=CGK&destination=LOP&date=2026-08-15&passengers=2&mode=pesawat
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin'      => 'required|string|max:10',
            'destination' => 'required|string|max:10',
            'date'        => 'required|date|after:today',
            'passengers'  => 'required|integer|min:1|max:9',
            'mode'        => 'nullable|in:pesawat,kereta,bus,kapal',
        ]);

        $results = $this->ticketService->search(
            origin:      strtoupper(trim($validated['origin'])),
            destination: strtoupper(trim($validated['destination'])),
            date:        $validated['date'],
            passengers:  (int) $validated['passengers'],
            mode:        $validated['mode'] ?? null,
        );

        return response()->json([
            'data' => TransportTicketResource::collection($results),
            'meta' => [
                'origin'      => strtoupper(trim($validated['origin'])),
                'destination' => strtoupper(trim($validated['destination'])),
                'date'        => $validated['date'],
                'passengers'  => (int) $validated['passengers'],
                'mode'        => $validated['mode'] ?? null,
                'total'       => count($results),
            ],
        ]);
    }

    /**
     * Detail satu tiket
     * 
     * GET /api/transport-tickets/{id}
     */
    public function show(int $id): JsonResponse
    {
        $ticket = \App\Models\TransportTicket::with('bookings')
            ->available()
            ->findOrFail($id);

        return response()->json([
            'data' => new TransportTicketDetailResource($ticket),
        ]);
    }
}
