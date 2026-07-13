<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransportTicketDetailResource;
use App\Http\Resources\TransportTicketResource;
use App\Models\TransportTicket;
use App\Services\TransportTicket\TransportTicketServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportTicketController extends Controller
{
    public function __construct(
        private TransportTicketServiceInterface $ticketService
    ) {}

    /**
     * Browse tiket (default: random upcoming, bisa difilter)
     * 
     * GET /api/transport-tickets?origin=CGK&destination=LOP&date=2026-08-15&mode=pesawat&min_price=100000&max_price=1000000&direct_only=1&sort=price_asc&page=1
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = TransportTicket::query()
            ->available()
            ->upcoming()
            ->when($request->origin, fn ($q, $o) => $q->where('origin_code', strtoupper($o)))
            ->when($request->destination, fn ($q, $d) => $q->where('destination_code', strtoupper($d)))
            ->when($request->date, fn ($q, $d) => $q->forDate($d))
            ->when($request->mode, fn ($q, $m) => $q->byMode($m))
            ->when($request->min_price, fn ($q, $p) => $q->minPrice((int) $p))
            ->when($request->max_price, fn ($q, $p) => $q->maxPrice((int) $p))
            ->when($request->direct_only, fn ($q) => $q->directOnly(true))
            ->when($request->passengers, fn ($q, $p) => $q->hasSeatsFor((int) $p))
            ->when($request->sort === 'price_asc', fn ($q) => $q->orderBy('price_per_ticket', 'asc'))
            ->when($request->sort === 'price_desc', fn ($q) => $q->orderBy('price_per_ticket', 'desc'))
            ->when($request->sort === 'departure_asc', fn ($q) => $q->orderBy('departure_time', 'asc'))
            ->when($request->sort === 'departure_desc', fn ($q) => $q->orderBy('departure_time', 'desc'))
            ->when(
                !$request->sort && !$request->origin && !$request->destination && !$request->mode && !$request->date,
                fn ($q) => $q->inRandomOrder()
            )
            ->paginate($request->per_page ?? 12);

        // Kalau random, load ulang dengan urutan departure_time supaya rapi
        if (!$request->sort && !$request->origin && !$request->destination && !$request->mode && !$request->date) {
            $ids = $tickets->pluck('id');
            $sorted = TransportTicket::whereIn('id', $ids)
                ->orderBy('departure_time', 'asc')
                ->get();
            $tickets->setCollection($sorted);
        }

        return response()->json([
            'data' => TransportTicketResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page'    => $tickets->lastPage(),
                'per_page'     => $tickets->perPage(),
                'total'        => $tickets->total(),
                'filters'      => [
                    'origin'      => $request->origin ?? null,
                    'destination' => $request->destination ?? null,
                    'date'        => $request->date ?? null,
                    'mode'        => $request->mode ?? null,
                    'min_price'   => $request->min_price ?? null,
                    'max_price'   => $request->max_price ?? null,
                    'direct_only' => $request->direct_only ?? null,
                    'sort'        => $request->sort ?? 'random',
                ],
            ],
        ]);
    }

    /**
     * Cari tiket tersedia (spesifik rute & tanggal - strict)
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
        $ticket = TransportTicket::with('bookings')
            ->available()
            ->findOrFail($id);

        return response()->json([
            'data' => new TransportTicketDetailResource($ticket),
        ]);
    }

    /**
     * Stats untuk filter UI
     * 
     * GET /api/transport-tickets/stats
     */
    public function stats(): JsonResponse
    {
        $base = TransportTicket::available()->upcoming();

        $stats = [
            'total'    => $base->count(),
            'pesawat'  => (clone $base)->where('transport_mode', 'pesawat')->count(),
            'kereta'   => (clone $base)->where('transport_mode', 'kereta')->count(),
            'bus'      => (clone $base)->where('transport_mode', 'bus')->count(),
            'kapal'    => (clone $base)->where('transport_mode', 'kapal')->count(),
            'min_price' => (clone $base)->min('price_per_ticket'),
            'max_price' => (clone $base)->max('price_per_ticket'),
        ];

        return response()->json(['data' => $stats]);
    }
}