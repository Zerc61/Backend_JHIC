<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Enums\ProductStatus;
use App\Enums\UmkmStatus;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function byUmkm(string $umkmSlug): JsonResponse
    {
        $products = Product::whereHas('umkm', fn($q) => $q->where('slug', $umkmSlug)->where('status', UmkmStatus::ACTIVE))
            ->where('status', ProductStatus::AVAILABLE)
            ->where('stock', '>', 0)
            ->get();

        return response()->json(['data' => ProductResource::collection($products)]);
    }
}