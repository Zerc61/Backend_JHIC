<?php

namespace App\Http\Controllers\Api\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Umkm;
use App\Enums\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UmkmDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $umkm = Umkm::where('user_id', $request->user()->id)->first();

        if (!$umkm) {
            return response()->json([
                'message' => 'Anda belum memiliki profil UMKM.',
                'data' => null,
            ], 200);
        }

        $orders = Order::where('umkm_id', $umkm->id);
        $products = Product::where('umkm_id', $umkm->id);

        $totalRevenue = (clone $orders)
            ->whereIn('status', [
                OrderStatus::PAID->value,
                OrderStatus::PREPARING->value,
                OrderStatus::READY->value,
                OrderStatus::PICKED_UP->value,
            ])
            ->sum('total_price');

        $ordersByStatus = (clone $orders)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $productsByStatus = (clone $products)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => [
                'umkm' => [
                    'id' => $umkm->id,
                    'name' => $umkm->name,
                    'slug' => $umkm->slug,
                    'status' => $umkm->status->value,
                ],
                'summary' => [
                    'total_products' => (clone $products)->count(),
                    'total_orders' => (clone $orders)->count(),
                    'total_revenue' => (float) $totalRevenue,
                    'total_revenue_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $totalRevenue),
                ],
                'orders_by_status' => $ordersByStatus,
                'products_by_status' => $productsByStatus,
                'recent_orders' => Order::with('user')
                    ->where('umkm_id', $umkm->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn ($o) => [
                        'id' => $o->id,
                        'order_number' => $o->order_number,
                        'total_price' => (float) $o->total_price,
                        'status' => $o->status->value,
                        'buyer_name' => $o->user?->name,
                        'created_at' => $o->created_at->toIso8601String(),
                    ]),
            ],
        ]);
    }
}
