<?php

namespace App\Services;

use App\Enums\CoinTransactionType;
use App\Models\Booking;
use App\Models\CoinTransaction;
use App\Models\LoyaltyReward;
use App\Models\Review;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public const EXPIRY_DAYS = 365;

    /** Urutan tier dari terendah ke tertinggi. */
    public const TIER_ORDER = ['bronze', 'silver', 'gold', 'platinum'];

    /** Ambang batas total coin yang diperoleh (earn) untuk naik tier. */
    public const TIER_THRESHOLDS = [
        'bronze' => 0,
        'silver' => 500,
        'gold' => 1500,
        'platinum' => 3000,
    ];

    public const REWARD = [
        'daily_login' => 10,
        'booking_first' => 200,
        'booking' => 50,
        'review' => 50,
        'review_photo' => 100,
        'complete_profile' => 50,
        'referral' => 500,
    ];

    /**
     * Pastikan user memiliki referral_code.
     */
    public function ensureReferralCode(User $user): string
    {
        if (empty($user->referral_code)) {
            $user->forceFill(['referral_code' => $this->generateReferralCode()])->save();
        }

        return $user->referral_code;
    }

    private function generateReferralCode(): string
    {
        do {
            $code = strtoupper(\Illuminate\Support\Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Beri reward coin (type=earn). Idempoten per (user, key).
     */
    public function earn(User $user, string $key, float $amount, string $description, ?EloquentModel $reference = null): ?CoinTransaction
    {
        if (LoyaltyReward::where('user_id', $user->id)->where('reward_key', $key)->exists()) {
            return null;
        }

        $wallet = $user->wallet;
        if (! $wallet) {
            return null;
        }

        return DB::transaction(function () use ($user, $wallet, $key, $amount, $description, $reference) {
            $tx = $wallet->credit($amount, $description, $reference);

            $tx->update([
                'type' => CoinTransactionType::EARN,
                'expires_at' => now()->addDays(self::EXPIRY_DAYS),
            ]);

            LoyaltyReward::create([
                'user_id' => $user->id,
                'reward_key' => $key,
                'coin_transaction_id' => $tx->id,
            ]);

            $user->forceFill(['last_coin_activity_at' => now()])->save();
            $this->recalculateTier($user);

            return $tx;
        });
    }

    /**
     * Total coin yang pernah diperoleh dari loyalty (kumulatif, tidak berkurang saat expire).
     */
    public function totalEarned(User $user): float
    {
        return (float) CoinTransaction::where('type', CoinTransactionType::EARN)
            ->whereHas('wallet', fn ($q) => $q->where('user_id', $user->id))
            ->sum('amount');
    }

    public function tierFor(float $totalEarned): string
    {
        $tier = 'bronze';
        foreach (self::TIER_THRESHOLDS as $name => $min) {
            if ($totalEarned >= $min) {
                $tier = $name;
            }
        }

        return $tier;
    }

    public function recalculateTier(User $user): string
    {
        $tier = $this->tierFor($this->totalEarned($user));
        if ($user->loyalty_tier !== $tier) {
            $user->forceFill(['loyalty_tier' => $tier])->save();
        }

        return $tier;
    }

    public function nextTier(User $user): ?array
    {
        $current = $this->tierFor($this->totalEarned($user));
        $order = self::TIER_ORDER;
        $idx = array_search($current, $order, true);
        $next = $order[$idx + 1] ?? null;

        if (! $next) {
            return null;
        }

        $total = $this->totalEarned($user);
        $currentThreshold = self::TIER_THRESHOLDS[$current];
        $nextThreshold = self::TIER_THRESHOLDS[$next];
        $progress = $nextThreshold > $currentThreshold
            ? max(0, min(100, (int) round(($total - $currentThreshold) / ($nextThreshold - $currentThreshold) * 100)))
            : 100;

        return [
            'tier' => $next,
            'threshold' => $nextThreshold,
            'remaining' => (float) max(0, $nextThreshold - $total),
            'progress_percent' => $progress,
        ];
    }

    // ================= REWARD BUNDLES =================

    public function rewardDailyLogin(User $user): ?CoinTransaction
    {
        return $this->earn($user, 'daily_login_' . now()->toDateString(), self::REWARD['daily_login'], 'Login harian');
    }

    public function rewardBooking(User $user, Booking $booking): void
    {
        $count = Booking::where('user_id', $user->id)->count();

        if ($count === 1) {
            $this->earn($user, 'booking_first', self::REWARD['booking_first'], 'Bonus booking pertama');
        }

        $this->earn($user, 'booking_' . $booking->id, self::REWARD['booking'], "Cashback booking #{$booking->booking_number}", $booking);
    }

    public function rewardReview(User $user, Review $review, bool $hasPhoto): void
    {
        $this->earn($user, 'review_' . $review->id, self::REWARD['review'], 'Memberi review', $review);

        if ($hasPhoto) {
            $this->earn($user, 'review_photo_' . $review->id, self::REWARD['review_photo'], 'Review dengan foto', $review);
        }
    }

    public function rewardCompleteProfile(User $user): void
    {
        if ($user->name && $user->phone && $user->avatar) {
            $this->earn($user, 'complete_profile', self::REWARD['complete_profile'], 'Melengkapi profil');
        }
    }

    public function rewardReferral(User $referrer, User $referee): void
    {
        if ($referrer->id === $referee->id) {
            return;
        }

        $this->earn($referrer, "referral_{$referrer->id}_{$referee->id}", self::REWARD['referral'], "Reward referal dari {$referee->name}", $referee);
    }

    // ================= EXPIRE =================

    /**
     * Menandai coin earn yang sudah jatuh tempo dan mengurangi saldo wallet.
     */
    public function expireCoins(): int
    {
        $expired = CoinTransaction::query()
            ->where('type', CoinTransactionType::EARN)
            ->where('is_expired', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired->groupBy('wallet_id') as $walletId => $txs) {
            $wallet = \App\Models\Wallet::find($walletId);
            if (! $wallet) {
                continue;
            }

            $sum = (float) $txs->sum('amount');
            if ($sum <= 0) {
                continue;
            }

            DB::transaction(function () use ($wallet, $sum, $txs) {
                $wallet = \App\Models\Wallet::where('id', $wallet->id)->lockForUpdate()->first();
                $amountToRemove = (float) max(0, min($wallet->balance, $sum));
                $before = $wallet->balance;

                if ($amountToRemove > 0) {
                    $wallet->decrement('balance', $amountToRemove);
                }

                $after = $wallet->fresh()->balance;

                CoinTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => CoinTransactionType::EXPIRE,
                    'amount' => $amountToRemove,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'description' => 'EJTCoin reward kedaluwarsa',
                ]);

                CoinTransaction::whereIn('id', $txs->pluck('id'))
                    ->update(['is_expired' => true]);
            });
        }

        return $expired->count();
    }

    // ================= REDEEM =================

    public function redeemVoucher(User $user, int $voucherId): array
    {
        $voucher = Voucher::find($voucherId);

        if (! $voucher || ! $voucher->is_active) {
            return ['valid' => false, 'message' => 'Voucher tidak ditemukan atau tidak aktif'];
        }

        if (LoyaltyReward::where('user_id', $user->id)->where('reward_key', "redeem_voucher_{$voucher->id}")->exists()) {
            return ['valid' => false, 'message' => 'Anda sudah menukar voucher ini'];
        }

        $conditions = is_array($voucher->conditions) ? $voucher->conditions : [];
        $minTier = $conditions['min_tier'] ?? 'bronze';
        $costCoins = (float) ($conditions['cost_coins'] ?? 0);

        if ($this->tierRank($user->loyalty_tier ?? 'bronze') < $this->tierRank($minTier)) {
            return ['valid' => false, 'message' => "Voucher ini hanya untuk tier {$minTier} ke atas"];
        }

        $wallet = $user->wallet;
        if (! $wallet) {
            return ['valid' => false, 'message' => 'Wallet tidak ditemukan'];
        }

        if ($costCoins > 0 && ! $wallet->hasSufficientBalance($costCoins)) {
            return ['valid' => false, 'message' => 'Saldo EJTCoin tidak mencukupi'];
        }

        DB::transaction(function () use ($user, $wallet, $voucher, $costCoins) {
            // Harus scoped ke PK wallet user; `$wallet->lockForUpdate()->first()` tanpa where mengunci wallet pertama di tabel.
            $wallet = Wallet::whereKey($wallet->getKey())->lockForUpdate()->first();

            if ($costCoins > 0) {
                if (! $wallet->hasSufficientBalance($costCoins)) {
                    throw new \RuntimeException('Saldo EJTCoin tidak mencukupi');
                }

                $tx = $wallet->debit($costCoins, "Tukar voucher {$voucher->code}", $voucher);
                $tx->update(['type' => CoinTransactionType::REDEEM]);
            } else {
                $balance = (float) $wallet->balance;

                CoinTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => CoinTransactionType::REDEEM,
                    'amount' => 0,
                    'balance_before' => $balance,
                    'balance_after' => $balance,
                    'description' => "Tukar voucher {$voucher->code}",
                    'reference_type' => Voucher::class,
                    'reference_id' => $voucher->id,
                ]);
            }

            LoyaltyReward::create([
                'user_id' => $user->id,
                'reward_key' => "redeem_voucher_{$voucher->id}",
            ]);

            VoucherClaim::firstOrCreate(
                ['user_id' => $user->id, 'voucher_id' => $voucher->id],
                [
                    'source' => 'loyalty',
                    'status' => 'unused',
                    'claimed_at' => now(),
                ],
            );

            $user->forceFill(['last_coin_activity_at' => now()])->save();
        });

        return [
            'valid' => true,
            'voucher' => $voucher,
            'code' => $voucher->code,
        ];
    }

    private function tierRank(string $tier): int
    {
        $idx = array_search($tier, self::TIER_ORDER, true);

        return $idx === false ? 0 : $idx;
    }
}