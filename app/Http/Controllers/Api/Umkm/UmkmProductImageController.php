<?php

namespace App\Http\Controllers\Api\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Umkm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UmkmProductImageController extends Controller
{
    private function getUmkm(Request $request): Umkm
    {
        return Umkm::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        if ($product->umkm_id !== $umkm->id) {
            abort(404);
        }

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('product-images', 'public');

        $image = $product->images()->create([
            'image' => $path,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Gambar produk berhasil ditambahkan.',
            'data' => [
                'id' => $image->id,
                'image' => url("storage/{$image->image}"),
                'sort_order' => $image->sort_order,
            ],
        ], 201);
    }

    public function destroy(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        if ($product->umkm_id !== $umkm->id) {
            abort(404);
        }

        if ($image->product_id !== $product->id) {
            abort(404);
        }

        Storage::disk('public')->delete($image->image);
        $image->delete();

        return response()->json(['message' => 'Gambar produk berhasil dihapus.']);
    }
}
