<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Enums\EventStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * List semua event (bisa filter by status)
     */
    public function index(Request $request): JsonResponse
    {
        $events = Event::with(['destination'])
            ->when($request->status, function ($query, $status) {
                // Validasi agar status yang dikirim sesuai enum
                if (in_array($status, array_column(EventStatus::cases(), 'value'))) {
                    $query->where('status', $status);
                }
            })
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('start_date', 'desc') // Event terdekat di atas
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'data' => EventResource::collection($events->items()),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * Detail event beserta gallery
     */
    public function show(Event $event): JsonResponse
    {
        // Load relasi galleries hanya saat lihat detail
        $event->load('destination', 'galleries');

        return response()->json([
            'data' => new EventResource($event),
        ]);
    }
}