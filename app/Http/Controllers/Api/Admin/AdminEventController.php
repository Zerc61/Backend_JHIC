<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGallery;
use App\Enums\EventStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;

class AdminEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::with(['destination', 'creator']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('destination_id')) {
            $query->where('destination_id', $request->destination_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        $events = $query->orderBy('start_date', 'desc')
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
            'destination_id' => 'nullable|exists:destinations,id',
            'location' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'status' => ['nullable', new Enum(EventStatus::class)],
        ]);

        $validated['slug'] = Event::generateUniqueSlug($validated['title']);
        $validated['created_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? EventStatus::UPCOMING->value;

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Event berhasil dibuat.',
            'data' => $event->load('destination', 'creator'),
        ], 201);
    }

    public function show(Event $event): JsonResponse
    {
        $event->load(['destination', 'creator', 'galleries']);

        return response()->json(['data' => $event]);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'destination_id' => 'nullable|exists:destinations,id',
            'location' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'status' => ['sometimes', new Enum(EventStatus::class)],
        ]);

        if (isset($validated['title']) && $validated['title'] !== $event->title) {
            $validated['slug'] = Event::generateUniqueSlug($validated['title']);
        }

        $event->update($validated);

        return response()->json([
            'message' => 'Event berhasil diupdate.',
            'data' => $event->load('destination', 'creator'),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(['message' => 'Event berhasil dihapus.']);
    }

    public function storeGallery(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
        ]);

        $path = $request->file('image')->store('event-galleries', 'public');
        $validated['image'] = $path;

        $gallery = $event->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(Event $event, EventGallery $gallery): JsonResponse
    {
        if ($gallery->event_id !== $event->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di event ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }
}