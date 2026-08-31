<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use App\Models\Order;
use App\Models\Booking;
use App\Models\VoucherClaim;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherService $voucherService)
    {
    }

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

        $preview = $this->voucherService->preview($request->user(), $voucher, (float) $request->amount);

        if (!$preview['valid']) {
            throw ValidationException::withMessages([
                'code' => $preview['message'],
            ]);
        }

        return response()->json([
            'valid' => true,
            'voucher_code' => $voucher->code,
            'discount' => (float) $preview['discount'],
            'original_amount' => (float) $request->amount,
            'final_amount' => (float) $preview['final_amount'],
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

        $applied = $this->voucherService->apply($request->user(), $request->voucher_code, (float) $order->total_price);

        $order->update([
            'discount' => $applied['discount'],
            'total_amount' => $applied['final_amount'],
            'voucher_id' => $applied['voucher_id'],
        ]);

        return response()->json([
            'message' => 'Voucher berhasil diterapkan',
            'data' => [
                'discount' => (float) $applied['discount'],
                'final_amount' => (float) $applied['final_amount'],
                'voucher_code' => $applied['code'],
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

        $applied = $this->voucherService->apply($request->user(), $request->voucher_code, (float) $booking->total_price);

        $booking->update([
            'discount' => $applied['discount'],
            'total_amount' => $applied['final_amount'],
            'voucher_id' => $applied['voucher_id'],
        ]);

        return response()->json([
            'message' => 'Voucher berhasil diterapkan',
            'data' => [
                'discount' => (float) $applied['discount'],
                'final_amount' => (float) $applied['final_amount'],
                'voucher_code' => $applied['code'],
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

    /**
     * Voucher gratis yang bisa diklaim (tampil di Home).
     */
    public function free(Request $request): JsonResponse
    {
        $vouchers = Voucher::active()
            ->where('is_free', true)
            ->latest('valid_from')
            ->get()
            ->filter(fn (Voucher $v) => $v->total_quota === null || $v->used_count < $v->total_quota)
            ->values();

        return response()->json([
            'data' => VoucherResource::collection($vouchers),
        ]);
    }

    /**
     * Klaim voucher gratis. Satu user hanya boleh klaim 1x per voucher.
     */
    public function claim(Request $request, Voucher $voucher): JsonResponse
    {
        if (! $voucher->is_free) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Voucher ini bukan voucher gratis.',
            ]);
        }

        if (! $voucher->isValid()) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Voucher tidak aktif atau sudah kedaluwarsa.',
            ]);
        }

        if ($voucher->total_quota && $voucher->used_count >= $voucher->total_quota) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Kuota voucher sudah habis.',
            ]);
        }

        $exists = VoucherClaim::where('user_id', $request->user()->id)
            ->where('voucher_id', $voucher->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Anda sudah memiliki voucher ini.',
            ]);
        }

        $claim = VoucherClaim::create([
            'user_id' => $request->user()->id,
            'voucher_id' => $voucher->id,
            'source' => 'free',
            'status' => 'unused',
            'claimed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Voucher berhasil diklaim.',
            'data' => $this->claimPayload($claim->load('voucher')),
        ], 201);
    }

    /**
     * Semua voucher yang dimiliki user (free + loyalty).
     */
    public function myVouchers(Request $request): JsonResponse
    {
        $claims = $request->user()->voucherClaims()
            ->with('voucher')
            ->latest('claimed_at')
            ->get();

        return response()->json([
            'data' => $claims->map(fn (VoucherClaim $claim) => $this->claimPayload($claim)),
        ]);
    }

    private function claimPayload(VoucherClaim $claim): array
    {
        return [
            'id' => $claim->id,
            'voucher_id' => $claim->voucher_id,
            'source' => $claim->source,
            'status' => $claim->status,
            'claimed_at' => $claim->claimed_at?->format('Y-m-d H:i:s'),
            'used_at' => $claim->used_at?->format('Y-m-d H:i:s'),
            'voucher' => new VoucherResource($claim->voucher),
        ];
    }
}
