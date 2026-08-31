<?php

namespace App\Http\Controllers\Api;

use App\Enums\CoinTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\CoinTransactionResource;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoyaltyController extends Controller
{
    public function __construct(private LoyaltyService $loyalty)
    {
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->loyalty->ensureReferralCode($user);
        $this->loyalty->expireCoins();

        $totalEarned = $this->loyalty->totalEarned($user);
        $tier = $this->loyalty->recalculateTier($user);

        $redeemableVouchers = Voucher::active()
            ->where('is_active', true)
            ->whereNotNull('conditions')
            ->latest('valid_from')
            ->get()
            ->filter(function (Voucher $v) use ($user) {
                if (! is_array($v->conditions) || (float) ($v->conditions['cost_coins'] ?? 0) <= 0) {
                    return false;
                }

                return ! $user->loyaltyRewards()
                    ->where('reward_key', "redeem_voucher_{$v->id}")
                    ->exists();
            })
            ->values();

        return response()->json([
            'data' => [
                'tier' => $tier,
                'total_earned' => (float) $totalEarned,
                'balance' => (float) ($user->wallet?->balance ?? 0),
                'referral_code' => $user->referral_code,
                'referral_link' => config('app.url') . '/register?ref=' . $user->referral_code,
                'next_tier' => $this->loyalty->nextTier($user),
                'redeemable_vouchers' => VoucherResource::collection($redeemableVouchers),
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return response()->json([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0],
            ]);
        }

        $transactions = $wallet->coinTransactions()
            ->whereIn('type', [CoinTransactionType::EARN, CoinTransactionType::REDEEM, CoinTransactionType::EXPIRE])
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => CoinTransactionResource::collection($transactions->items()),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function redeemVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'voucher_id' => 'required|integer|exists:vouchers,id',
        ]);

        $result = $this->loyalty->redeemVoucher($request->user(), (int) $request->voucher_id);

        if (! $result['valid']) {
            throw ValidationException::withMessages([
                'voucher_id' => $result['message'],
            ]);
        }

        return response()->json([
            'message' => 'Voucher berhasil ditukar',
            'data' => [
                'code' => $result['code'],
                'voucher' => new VoucherResource($result['voucher']),
            ],
        ]);
    }
}