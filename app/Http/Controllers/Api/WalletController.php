<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Http\Resources\CoinTransactionResource;
use App\Http\Resources\TopUpTransactionResource;
use App\Models\Wallet;
use App\Models\TopUpTransaction;
use App\Enums\TopUpStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;
        
        if (!$wallet) {
            // Fallback jika wallet belum ada (untuk user lama)
            $wallet = Wallet::create([
                'user_id' => $request->user()->id,
                'balance' => 0,
            ]);
        }
        
        return response()->json(['data' => new WalletResource($wallet)]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;
        
        if (!$wallet) {
            return response()->json(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0]]);
        }
        
        $transactions = $wallet->coinTransactions()->latest()->paginate(20);

        return response()->json([
            'data' => CoinTransactionResource::collection($transactions->items()),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function topUpHistory(Request $request): JsonResponse
    {
        $transactions = TopUpTransaction::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => TopUpTransactionResource::collection($transactions->items()),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

   public function requestTopUp(Request $request): JsonResponse
{
    $request->validate([
        'amount_rupiah' => 'required|integer|min:4000',
    ]);

    $user = $request->user();
    
    // Pastikan user punya wallet
    $wallet = $user->wallet ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);

    // Hitung coin yang akan didapat
    $ratePerCoin = 2000;
    $coinsReceived = floor($request->amount_rupiah / $ratePerCoin * 10000) / 10000;

    if ($coinsReceived <= 0) {
        return response()->json(['message' => 'Nominal terlalu kecil.'], 400);
    }

    // Buat transaksi top up status pending
    $topUp = TopUpTransaction::create([
        'user_id' => $user->id,
        'amount_rupiah' => $request->amount_rupiah,
        'rate_per_coin' => $ratePerCoin,
        'coins_received' => $coinsReceived,
        'status' => TopUpStatus::PENDING,
        'expired_at' => now()->addHours(24),
    ]);

    // Setup Midtrans Config
    \Midtrans\Config::$serverKey = config('midtrans.server_key');
    \Midtrans\Config::$isProduction = config('midtrans.is_production');
    \Midtrans\Config::$isSanitized = config('midtrans.is_sanitized', true);
    \Midtrans\Config::$is3ds = config('midtrans.is_3ds', true);

    // Split name untuk first_name dan last_name
    $nameParts = explode(' ', $user->name, 2);
    $firstName = $nameParts[0] ?? 'User';
    $lastName = $nameParts[1] ?? '';

    // FRONTEND URL untuk callback
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

    $payload = [
        'transaction_details' => [
            'order_id' => $topUp->midtrans_order_id,
            'gross_amount' => (int) $request->amount_rupiah,
        ],
        'customer_details' => [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
        ],
        'item_details' => [
            [
                'id' => 'TOPUP-COIN',
                'price' => (int) $request->amount_rupiah,
                'quantity' => 1,
                'name' => "Top Up {$coinsReceived} NusaCoin",
                'category' => 'Top Up Wallet',
            ]
        ],
        // FIX: Callback harus ke FRONTEND, bukan backend Laravel
        'callbacks' => [
            'finish' => $frontendUrl . '/wallet?status=finish',
            'error' => $frontendUrl . '/wallet?status=error',
            'pending' => $frontendUrl . '/wallet?status=pending',
        ]
    ];

    try {
        $snapToken = \Midtrans\Snap::getSnapToken($payload);
    } catch (\Exception $e) {
        \Log::error('Midtrans Error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Gagal membuat token pembayaran: ' . $e->getMessage()
        ], 500);
    }

    return response()->json([
        'data' => [
            'snap_token' => $snapToken,
            'order_id' => $topUp->midtrans_order_id,
            'coins_received' => $coinsReceived,
        ]
    ]);
}

    public function handleMidtransNotification(Request $request): JsonResponse
    {
        $payload = $request->all();
        
        // Validasi signature dari Midtrans (opsional tapi direkomendasikan)
        $signatureKey = $payload['signature_key'] ?? '';
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $serverKey = config('midtrans.server_key');
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signatureKey !== $expectedSignature) {
            \Log::warning('Invalid Midtrans signature for order: ' . $orderId);
            // Tetap proses untuk sandbox, tapi log warning
        }

        $topUp = TopUpTransaction::where('midtrans_order_id', $orderId)->first();

        if (!$topUp) {
            \Log::error('TopUp not found for order_id: ' . $orderId);
            return response()->json(['message' => 'Order tidak ditemukan'], 404);
        }

        $transactionStatus = $payload['transaction_status'] ?? '';

        // Jika pembayaran berhasil/sukses
        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            if ($topUp->isPending()) {
                DB::transaction(function () use ($topUp, $payload) {
                    $topUp->update([
                        'status' => TopUpStatus::SUCCESS,
                        'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                        'payment_type' => $payload['payment_type'] ?? null,
                        'va_number' => $this->extractVaNumber($payload),
                        'paid_at' => now(),
                        'midtrans_raw_response' => $payload,
                    ]);

                    // Tambahkan Coin ke Wallet
                    $topUp->user->wallet->credit(
                        (float) $topUp->coins_received,
                        "Top Up via " . ($payload['payment_type'] ?? 'Payment'),
                        $topUp
                    );
                });
            }
        }
        // Jika pending
        elseif ($transactionStatus == 'pending') {
            if ($topUp->isPending()) {
                $topUp->update([
                    'payment_type' => $payload['payment_type'] ?? null,
                    'va_number' => $this->extractVaNumber($payload),
                    'midtrans_raw_response' => $payload,
                ]);
            }
        }
        // Jika pembayaran gagal, expired, atau dibatalkan
        elseif (in_array($transactionStatus, ['cancel', 'expire', 'deny', 'failure'])) {
            if ($topUp->isPending()) {
                $topUp->update([
                    'status' => $transactionStatus == 'expire' ? TopUpStatus::EXPIRED : TopUpStatus::FAILED,
                    'midtrans_raw_response' => $payload,
                ]);
            }
        }
        // Jika refund
        elseif ($transactionStatus == 'refund') {
            if ($topUp->isSuccessful()) {
                DB::transaction(function () use ($topUp, $payload) {
                    $topUp->update([
                        'status' => TopUpStatus::REFUNDED,
                        'midtrans_raw_response' => $payload,
                    ]);

                    // Kurangi Coin dari Wallet
                    $topUp->user->wallet->debit(
                        (float) $topUp->coins_received,
                        "Refund Top Up " . $topUp->midtrans_order_id,
                        $topUp
                    );
                });
            }
        }

        return response()->json(['message' => 'Notifikasi diterima']);
    }

    private function extractVaNumber(array $payload): ?string
    {
        // Handle berbagai format VA number dari Midtrans
        if (isset($payload['va_numbers']) && is_array($payload['va_numbers']) && count($payload['va_numbers']) > 0) {
            return $payload['va_numbers'][0]['va_number'] ?? null;
        }
        
        if (isset($payload['permata_va_number'])) {
            return $payload['permata_va_number'];
        }

        if (isset($payload['biller_code']) && isset($payload['bill_key'])) {
            return $payload['biller_code'] . '-' . $payload['bill_key'];
        }

        return null;
    }

    public function checkTopUpStatus(string $orderId): JsonResponse
    {
        $topUp = TopUpTransaction::where('midtrans_order_id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'status' => $topUp->status->value,
            'coins_received' => (float) $topUp->coins_received,
        ]);
    }

    // SEMENTARA UNTUK SIMULASI WEBHOOK (HAPUS SETELAH SELESAI UJI COBA)
    public function simulateWebhook(string $orderId): JsonResponse
    {
        $topUp = TopUpTransaction::where('midtrans_order_id', $orderId)->firstOrFail();

        if ($topUp->isPending()) {
            DB::transaction(function () use ($topUp) {
                $topUp->update([
                    'status' => TopUpStatus::SUCCESS,
                    'midtrans_transaction_id' => 'SIM-' . Str::random(10),
                    'payment_type' => 'qris',
                    'paid_at' => now(),
                    'midtrans_raw_response' => ['status' => 'settlement', 'simulated' => true],
                ]);

                // Pastikan wallet ada
                $wallet = $topUp->user->wallet ?? Wallet::create([
                    'user_id' => $topUp->user->id,
                    'balance' => 0,
                ]);

                // Jalankan logika Credit ke Wallet
                $wallet->credit(
                    (float) $topUp->coins_received,
                    "Top Up via qris (Simulasi)",
                    $topUp
                );
            });
        }

        return response()->json([
            'message' => 'Webhook berhasil disimulasikan!',
            'current_status' => $topUp->fresh()->status->value,
            'coins_received' => (float) $topUp->coins_received,
            'wallet_balance' => (float) $topUp->user->wallet->balance
        ]);
    }
}