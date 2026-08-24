<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Booking;
use App\Models\Order;
use App\Models\BookingHold;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function initiateOrderPayment(Request $request, Order $order): JsonResponse
    {
        if ($request->user()->id !== $order->user_id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'payment_method' => 'required|in:card,bank_transfer,e_wallet,qris',
        ]);

        // Create payment transaction
        $payment = PaymentTransaction::createForOrder($order, $request->payment_method);

        // Setup Midtrans
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $nameParts = explode(' ', $request->user()->name, 2);
        $firstName = $nameParts[0] ?? 'User';
        $lastName = $nameParts[1] ?? '';

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->midtrans_order_id,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone ?? '',
            ],
            'item_details' => array_merge(
                $order->items->map(fn($item) => [
                    'id' => 'PROD-' . $item->product_id,
                    'price' => (int) $item->price,
                    'quantity' => $item->quantity,
                    'name' => $item->product_name,
                ])->toArray(),
                [[
                    'id' => 'DISCOUNT',
                    'price' => (int) ($order->discount ?? 0),
                    'quantity' => $order->discount ? 1 : 0,
                    'name' => 'Discount',
                ]]
            ),
            'callbacks' => [
                'finish' => env('FRONTEND_URL', 'http://localhost:5174') . "/orders/{$order->id}/payment-success",
                'error' => env('FRONTEND_URL', 'http://localhost:5174') . "/orders/{$order->id}/payment-error",
                'pending' => env('FRONTEND_URL', 'http://localhost:5174') . "/orders/{$order->id}/payment-pending",
            ],
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($payload);
        } catch (\Exception $e) {
            $payment->markAsFailed($e->getMessage());
            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat token pembayaran: ' . $e->getMessage(),
            ]);
        }

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'transaction_number' => $payment->transaction_number,
                'snap_token' => $snapToken,
                'amount' => (float) $payment->amount,
                'expires_at' => $payment->expires_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    public function initiateBookingPayment(Request $request, Booking $booking): JsonResponse
    {
        if ($request->user()->id !== $booking->user_id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'payment_method' => 'required|in:card,bank_transfer,e_wallet,qris',
        ]);

        // Create payment transaction
        $payment = PaymentTransaction::createForBooking($booking, $request->payment_method);

        // Create booking hold(s)
        if ($booking->booking_type === 'hotel' && $booking->hotelBooking) {
            BookingHold::createForHotelRoom($booking, $booking->hotelBooking->hotel_room_id, 1, 30);
        }

        // Setup Midtrans
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $nameParts = explode(' ', $request->user()->name, 2);
        $firstName = $nameParts[0] ?? 'User';
        $lastName = $nameParts[1] ?? '';

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->midtrans_order_id,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone ?? '',
            ],
            'item_details' => [
                [
                    'id' => 'BOOKING-' . $booking->booking_number,
                    'price' => (int) $payment->amount,
                    'quantity' => 1,
                    'name' => "Booking #{$booking->booking_number} ({$booking->booking_type})",
                ],
            ],
            'callbacks' => [
                'finish' => env('FRONTEND_URL', 'http://localhost:5174') . "/bookings/{$booking->booking_number}/payment-success",
                'error' => env('FRONTEND_URL', 'http://localhost:5174') . "/bookings/{$booking->booking_number}/payment-error",
                'pending' => env('FRONTEND_URL', 'http://localhost:5174') . "/bookings/{$booking->booking_number}/payment-pending",
            ],
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($payload);
        } catch (\Exception $e) {
            $payment->markAsFailed($e->getMessage());
            BookingHold::releaseForBooking($booking->id, 'payment_failed');
            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat token pembayaran: ' . $e->getMessage(),
            ]);
        }

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'transaction_number' => $payment->transaction_number,
                'snap_token' => $snapToken,
                'amount' => (float) $payment->amount,
                'expires_at' => $payment->expires_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    public function checkPaymentStatus(PaymentTransaction $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return response()->json([
            'data' => [
                'id' => $payment->id,
                'transaction_number' => $payment->transaction_number,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'status_label' => $payment->status_label,
                'expires_at' => $payment->expires_at->format('Y-m-d H:i:s'),
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function getPaymentHistory(Request $request): JsonResponse
    {
        $payments = PaymentTransaction::forUser($request->user()->id)
            ->when($request->status, fn($q, $status) => $q->byStatus($status))
            ->recent()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => $payments->map(fn($p) => [
                'id' => $p->id,
                'transaction_number' => $p->transaction_number,
                'amount' => (float) $p->amount,
                'amount_formatted' => $p->formatted_amount,
                'status' => $p->status,
                'status_label' => $p->status_label,
                'payment_method' => $p->payment_method,
                'created_at' => $p->created_at->format('Y-m-d H:i:s'),
                'paid_at' => $p->paid_at?->format('Y-m-d H:i:s'),
            ]),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function handlePaymentCallback(Request $request): JsonResponse
    {
        $orderId = $request->order_id;
        $transactionStatus = $request->transaction_status;
        $signatureKey = $request->signature_key;

        // Verify signature
        $serverKey = config('midtrans.server_key');
        $statusCode = $request->status_code;
        $grossAmount = $request->gross_amount;
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signatureKey !== $expectedSignature) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payment = PaymentTransaction::where('midtrans_order_id', $orderId)->first();
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            DB::transaction(function () use ($payment, $request) {
                $payment->markAsSuccess($request->all());
                BookingHold::releaseForBooking($payment->payable_id, 'payment_completed');
            });
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'failure', 'expire'])) {
            $payment->markAsFailed($transactionStatus);
            BookingHold::releaseForBooking($payment->payable_id, 'payment_failed');
        }

        return response()->json(['message' => 'Payment callback processed']);
    }
}
