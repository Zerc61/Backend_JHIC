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
    public function index(Request $request): JsonResponse
    {
        $events = Event::with('destination')
            ->whereIn('status', [EventStatus::UPCOMING, EventStatus::ONGOING])
            ->latest('start_date')
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

    public function show(string $slug): JsonResponse
    {
        $event = Event::with(['destination', 'galleries', 'creator'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => new EventResource($event)]);
    }
}