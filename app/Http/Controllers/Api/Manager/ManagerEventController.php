<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Event;
use App\Models\EventGallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManagerEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::with(['destination'])
            ->where('created_by', auth()->id())
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy('start_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($events);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'destination_id' => 'required|exists:destinations,id',
            'location' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'status' => 'nullable|in:upcoming,ongoing,finished,cancelled',
        ]);

        // Pastikan destination milik manager ini
        $destination = Destination::where('id', $validated['destination_id'])
            ->where('manager_id', auth()->id())
            ->first();

        if (!$destination) {
            return response()->json([
                'message' => 'Anda hanya bisa membuat event di destination milik sendiri.',
            ], 403);
        }

            $validated['slug'] = Event::generateUniqueSlug($validated['title']);
        $validated['created_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'upcoming';

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Event berhasil dibuat.',
            'data' => $event->load('destination'),
        ], 201);
    }

    public function show(Event $event): JsonResponse
    {
        $this->verifyOwnership($event);
        $event->load(['destination', 'galleries']);

        return response()->json(['data' => $event]);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $this->verifyOwnership($event);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'destination_id' => 'nullable|exists:destinations,id',
            'location' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'status' => 'sometimes|in:upcoming,ongoing,finished,cancelled',
        ]);

        // Kalau ganti destination, pastikan masih miliknya
        if (isset($validated['destination_id'])) {
            $destination = Destination::where('id', $validated['destination_id'])
                ->where('manager_id', auth()->id())
                ->first();

            if (!$destination) {
                return response()->json([
                    'message' => 'Anda hanya bisa mengaitkan event ke destination milik sendiri.',
                ], 403);
            }
        }

        if (isset($validated['title']) && $validated['title'] !== $event->title) {
        $validated['slug'] = Event::generateUniqueSlug($validated['title']);
        }

        $event->update($validated);

        return response()->json([
            'message' => 'Event berhasil diupdate.',
            'data' => $event->load('destination'),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->verifyOwnership($event);
        $event->delete();

        return response()->json(['message' => 'Event berhasil dihapus.']);
    }

    public function storeGallery(Request $request, Event $event): JsonResponse
    {
        $this->verifyOwnership($event);

        $validated = $request->validate([
            'image' => 'required|string|max:500',
            'caption' => 'nullable|string|max:255',
        ]);

        $gallery = $event->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(Event $event, EventGallery $gallery): JsonResponse
    {
        $this->verifyOwnership($event);

        if ($gallery->event_id !== $event->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di event ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }

    private function verifyOwnership(Event $event): void
    {
        if ($event->created_by !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke event ini.');
        }
    }
}