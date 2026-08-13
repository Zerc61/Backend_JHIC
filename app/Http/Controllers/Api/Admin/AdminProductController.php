<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['umkm.user', 'umkm.destination']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('umkm_id')) {
            $query->where('umkm_id', $request->umkm_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $products = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($products);
    }

    public function pending(Request $request): JsonResponse
    {
        $products = Product::with(['umkm.user', 'umkm.destination'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 15);

        return response()->json($products);
    }

    public function rejected(Request $request): JsonResponse
    {
        $products = Product::with(['umkm.user', 'umkm.destination'])
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['umkm.user', 'umkm.destination', 'images']);

        return response()->json(['data' => $product]);
    }

    public function approve(Request $request, Product $product): JsonResponse
    {
        if (!in_array($product->status, ['pending', 'rejected'])) {
            return response()->json([
                'message' => "Produk ini statusnya {$product->status}, tidak bisa di-approve.",
            ], 422);
        }

        $product->update([
            'status' => 'available',
            'admin_note' => null,
        ]);

        return response()->json([
            'message' => 'Produk berhasil di-approve.',
            'data' => $product->load('umkm.user'),
        ]);
    }

    public function reject(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        if (!in_array($product->status, ['pending', 'approved', 'available'])) {
            return response()->json([
                'message' => "Produk ini statusnya {$product->status}, tidak bisa di-reject.",
            ], 422);
        }

        $product->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'],
        ]);

        return response()->json([
            'message' => 'Produk berhasil di-reject.',
            'data' => $product->load('umkm.user'),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:500',
            'status' => 'sometimes|in:pending,approved,rejected,available,unavailable',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $product->update($validated);

        return response()->json(['message' => 'Produk berhasil diupdate.', 'data' => $product]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}