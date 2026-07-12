<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
// use App\Models\OrderItem;
use App\Models\Product;
// use App\Models\Wallet;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
{
    $orders = Order::with(['items', 'umkm'])
        ->where('user_id', $request->user()->id)
        ->when($request->status, function ($query, $status) {
            $statuses = explode(',', $status);
            $query->whereIn('status', $statuses);
        })
        ->latest()
        ->paginate($request->per_page ?? 10);

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
       if ($request->user()->id !== $order->user_id) {
    abort(403, 'Kamu tidak memiliki akses ke pesanan ini.');
}
        $order->load('items', 'umkm');
        return response()->json(['data' => new OrderResource($order)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:coin,cash_on_pickup',
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $wallet = $user->wallet;
            $paymentMethod = PaymentMethod::from($request->payment_method);
            $totalPrice = 0;
            $orderItemsData = [];

            // 1. Validasi Produk & Hitung Total
            foreach ($request->items as $item) {
    $product = Product::where('id', $item['product_id'])
        ->where('status', ProductStatus::AVAILABLE)
        ->lockForUpdate()
        ->first();

    if (!$product) {
        abort(400, "Produk dengan ID {$item['product_id']} tidak tersedia.");
    }
    if ($product->stock < $item['quantity']) {
        abort(400, "Stok {$product->name} tidak mencukupi. Sisa: {$product->stock}");
    }

                $subtotal = $product->price * $item['quantity'];
                $totalPrice += $subtotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            // 2. Proses Pembayaran Coin
            $coinAmount = 0;
            $coinToRupiahRate = 2000; // 1 coin = Rp 2000
            $rupiahEquivalent = 0;

            if ($paymentMethod === PaymentMethod::COIN) {
    if (!$wallet) {
        abort(400, "Wallet tidak ditemukan. Silakan hubungi admin.");
    }

    $coinAmount = ceil($totalPrice / $coinToRupiahRate * 10000) / 10000;
    
    if (!$wallet->hasSufficientBalance($coinAmount)) {
        abort(400, "Saldo coin tidak mencukupi. Dibutuhkan: {$coinAmount} Coin.");
    }

                // Potong saldo
                $wallet->debit($coinAmount, "Pembayaran Order Pickup", $order ?? null); 
                // Catatan: $order masih null di sini, tapi kita akan update reference_id di bawah setelah order dibuat
                $rupiahEquivalent = $totalPrice;
            }

            // 3. Buat Order
            $order = Order::create([
                'user_id' => $user->id,
                'umkm_id' => $orderItemsData[0]['product_id'] ? Product::find($orderItemsData[0]['product_id'])->umkm_id : null,
                'total_price' => $totalPrice,
                'status' => $paymentMethod === PaymentMethod::COIN ? OrderStatus::PAID : OrderStatus::PENDING,
                'payment_method' => $paymentMethod,
                'coin_amount' => $coinAmount,
                'coin_to_rupiah_rate' => $coinToRupiahRate,
                'rupiah_equivalent' => $rupiahEquivalent,
                'notes' => $request->notes,
                'paid_at' => $paymentMethod === PaymentMethod::COIN ? now() : null,
            ]);

            // 4. Update reference_id di Coin Transaction (karena tadi $order belum ada)
            if ($paymentMethod === PaymentMethod::COIN) {
                $latestCoinTx = $wallet->coinTransactions()->latest()->first();
                if ($latestCoinTx) {
                    $latestCoinTx->update([
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                    ]);
                }
            }

            // 5. Simpan Order Items & Kurangi Stok
            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
                
                // Kurangi stok produk
                Product::where('id', $itemData['product_id'])->decrement('stock', $itemData['quantity']);
            }

            // 6. Generate QR Code
            $qrContent = $order->order_number;
            $qrSvg = QrCode::format('svg')->size(200)->generate($qrContent);
            $order->update(['qr_code' => $qrSvg]);

            return response()->json([
                'message' => 'Pesanan berhasil dibuat!',
                'data' => new OrderResource($order->load('items', 'umkm')),
            ], 201);
        });
    }

    public function cancel(Request $request, Order $order): JsonResponse
{
    // Validasi kepemilikan
    if ($request->user()->id !== $order->user_id) {
        abort(403, 'Kamu tidak memiliki akses ke pesanan ini.');
    }

    // Validasi status: hanya pending & paid yang bisa dibatalkan
    if (!in_array($order->status->value, [OrderStatus::PENDING->value, OrderStatus::PAID->value])) {
        abort(400, 'Pesanan tidak dapat dibatalkan karena sudah diproses.');
    }

    return DB::transaction(function () use ($order, $request) {
        $oldStatus = $order->status;

        // Update status
        $order->update([
            'status' => OrderStatus::CANCELLED,
        ]);

        // Jika bayar pakai coin, kembalikan saldo
        if ($oldStatus === OrderStatus::PAID && $order->coin_amount > 0) {
            $wallet = $order->user->wallet;
            if ($wallet) {
                $wallet->credit($order->coin_amount, "Pengembalian coin - Pesanan {$order->order_number} dibatalkan", $order);
            }
        }

        // (Opsional) Kembalikan stok produk
        foreach ($order->items as $item) {
            Product::where('id', $item->product_id)->increment('stock', $item->quantity);
        }

        return response()->json([
            'message' => 'Pesanan berhasil dibatalkan.',
            'data' => new OrderResource($order->load('items', 'umkm')),
        ]);
    });
}
}