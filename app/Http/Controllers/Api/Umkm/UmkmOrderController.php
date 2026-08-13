<?php

namespace App\Http\Controllers\Api\Umkm;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Umkm;
use App\Enums\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UmkmOrderController extends Controller
{
    private function getUmkm(Request $request): Umkm
    {
        return Umkm::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        $orders = Order::with(['items', 'user'])
            ->where('umkm_id', $umkm->id)
            ->when($request->filled('status'), function ($q) use ($request) {
                $statuses = explode(',', $request->status);
                $q->whereIn('status', $statuses);
            })
            ->when($request->filled('payment_method'), fn($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('search'), fn($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        if ($order->umkm_id !== $umkm->id) {
            abort(404);
        }

        $order->load(['items', 'user']);

        return response()->json(['data' => new OrderResource($order)]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $umkm = $this->getUmkm($request);

        if ($order->umkm_id !== $umkm->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => 'required|in:preparing,ready,picked_up',
        ]);

        $newStatus = $validated['status'];
        $current = $order->status->value;

        $allowedTransitions = [
            'paid' => ['preparing', 'ready', 'picked_up'],
            'preparing' => ['ready', 'picked_up'],
            'ready' => ['picked_up'],
            'pending' => [],
        ];

        if (!in_array($newStatus, $allowedTransitions[$current] ?? [])) {
            return response()->json([
                'message' => "Tidak bisa mengubah status dari {$current} ke {$newStatus}.",
            ], 422);
        }

        $order->status = $newStatus;

        if ($newStatus === 'picked_up') {
            $order->picked_up_at = now();
        }

        $order->save();

        return response()->json([
            'message' => 'Status pesanan berhasil diupdate.',
            'data' => new OrderResource($order->load('items'))  ,
        ]);
    }
}
