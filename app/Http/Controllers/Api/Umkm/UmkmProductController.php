<?php

namespace App\Http\Controllers\Api\Umkm;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Umkm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UmkmProductController extends Controller
{
    private function getUmkm(Request $request): Umkm
    {
        return Umkm::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        $products = Product::where('umkm_id', $umkm->id)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->with('images')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $validated['umkm_id'] = $umkm->id;
        $validated['slug'] = Product::generateUniqueSlug($validated['name']);

        if (!empty($validated['image'])) {
            $validated['image'] = $request->file('image')->store('product-images', 'public');
        }

        $validated['status'] = 'pending';

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Produk berhasil dibuat. Menunggu persetujuan admin.',
            'data' => new ProductResource($product->load('images')),
        ], 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        if ($product->umkm_id !== $umkm->id) {
            abort(404);
        }

        $product->load('images');

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        if ($product->umkm_id !== $umkm->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'unit' => 'nullable|string|max:50',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $product->name) {
            $validated['slug'] = Product::generateUniqueSlug($validated['name']);
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Produk berhasil diupdate.',
            'data' => new ProductResource($product->load('images')),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        if ($product->umkm_id !== $umkm->id) {
            abort(404);
        }

        if ($product->orderItems()->exists()) {
            return response()->json([
                'message' => 'Produk memiliki riwayat pesanan dan tidak bisa dihapus.',
            ], 422);
        }

        $product->images()->each(function ($image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image);
        });
        $product->images()->delete();
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
