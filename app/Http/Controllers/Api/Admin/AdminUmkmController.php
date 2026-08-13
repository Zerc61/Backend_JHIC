<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUmkmController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Umkm::with(['user', 'destination', 'category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('umkm_category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $umkms = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($umkms);
    }

    public function pending(Request $request): JsonResponse
    {
        $umkms = Umkm::with(['user', 'destination', 'category'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 15);

        return response()->json($umkms);
    }

    public function rejected(Request $request): JsonResponse
    {
        $umkms = Umkm::with(['user', 'destination', 'category'])
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($umkms);
    }

    public function show(Umkm $umkm): JsonResponse
    {
        $umkm->load(['user', 'destination', 'category', 'products']);

        return response()->json(['data' => $umkm]);
    }

    public function approve(Request $request, Umkm $umkm): JsonResponse
    {
        if ($umkm->status !== 'pending') {
            return response()->json([
                'message' => "UMKM ini statusnya {$umkm->status}, bukan pending. Tidak bisa di-approve.",
            ], 422);
        }

        $umkm->update([
            'status' => 'active',
            'admin_note' => null,
        ]);

        return response()->json([
            'message' => 'UMKM berhasil di-approve.',
            'data' => $umkm->load('user'),
        ]);
    }

    public function reject(Request $request, Umkm $umkm): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        if ($umkm->status !== 'pending') {
            return response()->json([
                'message' => "UMKM ini statusnya {$umkm->status}, bukan pending. Tidak bisa di-reject.",
            ], 422);
        }

        $umkm->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'],
        ]);

        return response()->json([
            'message' => 'UMKM berhasil di-reject.',
            'data' => $umkm->load('user'),
        ]);
    }

    // update & destroy kalau admin ingin edit/hapus langsung
    public function update(Request $request, Umkm $umkm): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|string|max:500',
            'phone' => 'sometimes|string|max:30',
            'opening_hours' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive,pending,rejected',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $umkm->update($validated);

        return response()->json(['message' => 'UMKM berhasil diupdate.', 'data' => $umkm]);
    }

    public function destroy(Umkm $umkm): JsonResponse
    {
        $umkm->delete();

        return response()->json(['message' => 'UMKM berhasil dihapus.']);
    }
}