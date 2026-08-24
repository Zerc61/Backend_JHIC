<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Order;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::byCode($request->code)->first();

        if (!$voucher) {
            throw ValidationException::withMessages([
                'code' => 'Kode voucher tidak ditemukan',
            ]);
        }

        if (!$voucher->isValid()) {
            throw ValidationException::withMessages([
                'code' => 'Voucher tidak valid atau sudah expired',
            ]);
        }

        $userValidation = $voucher->canUseByUser($request->user()->id);
        if (!$userValidation['valid']) {
            throw ValidationException::withMessages([
                'code' => $userValidation['message'],
            ]);
        }

        $discount = $voucher->calculateDiscount($request->amount);

        return response()->json([
            'valid' => true,
            'voucher_code' => $voucher->code,
            'discount' => (float) $discount['discount'],
            'original_amount' => (float) $request->amount,
            'final_amount' => (float) $discount['final_amount'],
            'discount_percentage' => $voucher->discount_type === 'percentage' ? (float) $voucher->discount_value : 0,
        ]);
    }

    public function applyToOrder(Request $request, Order $order): JsonResponse
    {
        if ($request->user()->id !== $order->user_id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'voucher_code' => 'required|string',
        ]);

        $voucher = Voucher::byCode($request->voucher_code)->first();

        if (!$voucher) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Kode voucher tidak ditemukan',
            ]);
        }

        $result = $voucher->applyToOrder($order, $request->user()->id);

        if (!$result['valid']) {
            throw ValidationException::withMessages([
                'voucher_code' => $result['message'],
            ]);
        }

        // Update order with discount
        $order->update([
            'discount' => $result['discount'],
            'total_amount' => $result['final_amount'],
            'voucher_id' => $voucher->id,
        ]);

        return response()->json([
            'message' => 'Voucher berhasil diterapkan',
            'data' => [
                'discount' => (float) $result['discount'],
                'final_amount' => (float) $result['final_amount'],
                'usage_id' => $result['usage_id'],
            ],
        ]);
    }

    public function applyToBooking(Request $request, Booking $booking): JsonResponse
    {
        if ($request->user()->id !== $booking->user_id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'voucher_code' => 'required|string',
        ]);

        $voucher = Voucher::byCode($request->voucher_code)->first();

        if (!$voucher) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Kode voucher tidak ditemukan',
            ]);
        }

        $result = $voucher->applyToBooking($booking, $request->user()->id);

        if (!$result['valid']) {
            throw ValidationException::withMessages([
                'voucher_code' => $result['message'],
            ]);
        }

        // Update booking with discount
        $booking->update([
            'discount' => $result['discount'],
            'total_amount' => $result['final_amount'],
            'voucher_id' => $voucher->id,
        ]);

        return response()->json([
            'message' => 'Voucher berhasil diterapkan',
            'data' => [
                'discount' => (float) $result['discount'],
                'final_amount' => (float) $result['final_amount'],
                'usage_id' => $result['usage_id'],
            ],
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $vouchers = Voucher::active()
            ->where('is_active', true)
            ->latest('valid_from')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => $vouchers->map(fn($v) => [
                'id' => $v->id,
                'code' => $v->code,
                'description' => $v->description,
                'discount_type' => $v->discount_type,
                'discount_value' => (float) $v->discount_value,
                'min_purchase' => (float) $v->min_purchase,
                'valid_until' => $v->valid_until->format('Y-m-d H:i:s'),
                'quota_remaining' => $v->total_quota ? $v->total_quota - $v->used_count : null,
            ]),
            'meta' => [
                'current_page' => $vouchers->currentPage(),
                'last_page' => $vouchers->lastPage(),
                'total' => $vouchers->total(),
            ],
        ]);
    }
}
