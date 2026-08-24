<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Booking;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $refunds = Refund::forUser($request->user()->id)
            ->when($request->status, fn($q, $status) => $q->byStatus($status))
            ->recent()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => $refunds->map(fn($r) => [
                'id' => $r->id,
                'refund_number' => $r->refund_number,
                'reason' => $r->reason,
                'refund_amount' => (float) $r->refund_amount,
                'refund_amount_formatted' => $r->formatted_amount,
                'status' => $r->status,
                'status_label' => $r->status_label,
                'created_at' => $r->created_at->format('Y-m-d H:i:s'),
                'completed_at' => $r->completed_at?->format('Y-m-d H:i:s'),
            ]),
            'meta' => [
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
                'total' => $refunds->total(),
            ],
        ]);
    }

    public function show(Refund $refund): JsonResponse
    {
        $this->authorize('view', $refund);

        return response()->json([
            'data' => [
                'id' => $refund->id,
                'refund_number' => $refund->refund_number,
                'reason' => $refund->reason,
                'description' => $refund->description,
                'refund_amount' => (float) $refund->refund_amount,
                'refund_amount_formatted' => $refund->formatted_amount,
                'refund_method' => $refund->refund_method,
                'status' => $refund->status,
                'status_label' => $refund->status_label,
                'approved_by' => $refund->approvedBy?->name,
                'approval_notes' => $refund->approval_notes,
                'transaction_reference' => $refund->transaction_reference,
                'refundable_type' => $refund->refundable_type,
                'created_at' => $refund->created_at->format('Y-m-d H:i:s'),
                'approved_at' => $refund->approved_at?->format('Y-m-d H:i:s'),
                'completed_at' => $refund->completed_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function requestOrderRefund(Request $request, Order $order): JsonResponse
    {
        if ($request->user()->id !== $order->user_id) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($order->status->value, ['paid', 'pending'])) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan tidak dapat direfund pada status ini',
            ]);
        }

        $request->validate([
            'reason' => 'required|in:order_cancelled,duplicate_payment,customer_request',
            'description' => 'required|string|min:10|max:500',
        ]);

        $refund = Refund::createForOrder(
            $order,
            $request->reason,
            $request->description
        );

        return response()->json([
            'message' => 'Permintaan refund berhasil dibuat',
            'data' => [
                'refund_number' => $refund->refund_number,
                'status' => $refund->status,
            ],
        ], 201);
    }

    public function requestBookingRefund(Request $request, Booking $booking): JsonResponse
    {
        if ($request->user()->id !== $booking->user_id) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($booking->status, ['paid', 'pending'])) {
            throw ValidationException::withMessages([
                'booking' => 'Booking tidak dapat direfund pada status ini',
            ]);
        }

        $request->validate([
            'reason' => 'required|in:booking_cancelled,duplicate_booking,customer_request',
            'description' => 'required|string|min:10|max:500',
        ]);

        $refund = Refund::createForBooking(
            $booking,
            $request->reason,
            $request->description
        );

        return response()->json([
            'message' => 'Permintaan refund berhasil dibuat',
            'data' => [
                'refund_number' => $refund->refund_number,
                'status' => $refund->status,
            ],
        ], 201);
    }
}
