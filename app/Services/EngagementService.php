<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\TopUpStatus;
use App\Models\Booking;
use App\Models\LoyaltyReward;
use App\Models\Notification;
use App\Models\Review;
use App\Models\TopUpTransaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use Illuminate\Support\Facades\DB;

/**
 * Fitur engagement: Daily Streak (klaim harian 7 hari) dan Quest.
 * Reward coin direkam via LoyaltyService::earn sehingga ikut tier & riwayat.
 */
class EngagementService
{
    /** Reward coin per hari dalam siklus 7 hari (hari ke-7 = coin maks + voucher). */
    public const DAILY_STREAK_REWARDS = [10, 15, 20, 25, 30, 40, 50];

    public const DAILY_DAY7_VOUCHER_CODE = 'DAILY7';

    public const QUESTS = [
        ['slug' => 'first_booking', 'title' => 'Booking Pertama', 'description' => 'Selesaikan booking pertamamu', 'reward_coins' => 50, 'icon' => 'ticket'],
        ['slug' => 'five_bookings', 'title' => 'Petualang Ulung', 'description' => 'Lakukan 5 booking', 'reward_coins' => 100, 'icon' => 'ticket'],
        ['slug' => 'give_review', 'title' => 'Beri Rating', 'description' => 'Tulis ulasan untuk destinasi yang kamu kunjungi', 'reward_coins' => 50, 'icon' => 'star'],
        ['slug' => 'complete_profile', 'title' => 'Lengkapi Profil', 'description' => 'Lengkapi nama, nomor HP, dan foto profil', 'reward_coins' => 30, 'icon' => 'user'],
        ['slug' => 'claim_free_voucher', 'title' => 'Klaim Voucher Gratis', 'description' => 'Klaim voucher gratis di halaman utama', 'reward_coins' => 30, 'icon' => 'gift'],
        ['slug' => 'exchange_voucher', 'title' => 'Tukar Voucher', 'description' => 'Tukar voucher EJTCoin di halaman loyalty', 'reward_coins' => 40, 'icon' => 'exchange'],
        ['slug' => 'first_topup', 'title' => 'Top Up Pertama', 'description' => 'Lakukan top up EJTCoin pertamamu', 'reward_coins' => 100, 'icon' => 'coins'],
    ];

    public function __construct(private LoyaltyService $loyalty)
    {
    }

    // ================= DAILY STREAK =================

    /**
     * Status streak harian konsisten dengan tanggal now().
     */
    public function dailyStatus(User $user): array
    {
        $dates = $this->claimedDates($user);
        $today = now()->toDateString();
        $claimedToday = isset($dates[$today]);

        $cursor = $claimedToday ? now() : now()->subDay();
        $streak = 0;
        while (isset($dates[$cursor->toDateString()])) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        $index = $streak % count(self::DAILY_STREAK_REWARDS);
        $nextVoucher = $index === count(self::DAILY_STREAK_REWARDS) - 1;

        return [
            'claimed_today' => $claimedToday,
            'streak_days' => $streak,
            'next_day' => $index + 1,
            'next_day_coins' => (float) self::DAILY_STREAK_REWARDS[$index],
            'next_day_voucher' => $nextVoucher,
            'cycle_days' => count(self::DAILY_STREAK_REWARDS),
            'rewards' => array_map(fn ($coins) => (float) $coins, self::DAILY_STREAK_REWARDS),
        ];
    }

    /**
     * Klaim bonus harian (sekali per hari, idempoten via reward_key daily_{tanggal}).
     */
    public function claimDaily(User $user): array
    {
        $status = $this->dailyStatus($user);

        if ($status['claimed_today']) {
            return ['valid' => false, 'message' => 'Kamu sudah mengklaim bonus hari ini. Kembali lagi besok!'];
        }

        if (! $user->wallet) {
            return ['valid' => false, 'message' => 'Wallet tidak ditemukan'];
        }

        $day = $status['next_day'];
        $coins = (float) $status['next_day_coins'];
        $isDay7 = $status['next_day_voucher'];

        DB::transaction(function () use ($user, $coins, $day, $isDay7) {
            $description = "Klaim harian hari ke-{$day} — +{$coins} EJTCoin";

            if ($isDay7) {
                $description .= ' + voucher gratis';
            }

            $this->loyalty->earn($user, 'daily_' . now()->toDateString(), $coins, $description);

            if ($isDay7) {
                $this->grantDay7Voucher($user);
            }

            \App\Models\Notification::createDailyReward($user, $coins, $isDay7 ? self::DAILY_DAY7_VOUCHER_CODE : null);
        });

        return [
            'valid' => true,
            'coins' => $coins,
            'day' => $day,
            'voucher_code' => $isDay7 ? self::DAILY_DAY7_VOUCHER_CODE : null,
        ];
    }

    private function grantDay7Voucher(User $user): void
    {
        $rewardKey = 'daily_voucher_' . now()->toDateString();

        if (LoyaltyReward::where('user_id', $user->id)->where('reward_key', $rewardKey)->exists()) {
            return;
        }

        $voucher = Voucher::where('code', self::DAILY_DAY7_VOUCHER_CODE)
            ->where('is_active', true)
            ->first();

        LoyaltyReward::create([
            'user_id' => $user->id,
            'reward_key' => $rewardKey,
        ]);

        // Voucher admin "DAILY7" wajib ada untuk hadiah hari ke-7; jika belum dibuat, hadir tanpa voucher.
        if ($voucher) {
            VoucherClaim::firstOrCreate(
                ['user_id' => $user->id, 'voucher_id' => $voucher->id],
                ['source' => 'daily', 'status' => 'unused', 'claimed_at' => now()],
            );
        }
    }

    /**
     * Tanggal (Y-m-d) yang sudah diklaim user, sebagai key array.
     */
    private function claimedDates(User $user): array
    {
        return LoyaltyReward::where('user_id', $user->id)
            ->where('reward_key', 'like', 'daily\_%')
            ->pluck('reward_key')
            ->map(function (string $key): ?string {
                return preg_match('/^daily_\d{4}-\d{2}-\d{2}$/', $key) === 1 ? substr($key, 6) : null;
            })
            ->filter()
            ->flip()
            ->all();
    }

    // ================= QUEST =================

    public function quests(User $user): array
    {
        $claimed = LoyaltyReward::where('user_id', $user->id)
            ->where('reward_key', 'like', 'quest\_%')
            ->pluck('reward_key')
            ->all();

        return array_map(function (array $quest) use ($user, $claimed) {
            return [
                'slug' => $quest['slug'],
                'title' => $quest['title'],
                'description' => $quest['description'],
                'reward_coins' => (float) $quest['reward_coins'],
                'icon' => $quest['icon'],
                'accomplished' => $this->questCompleted($user, $quest['slug']),
                'claimed' => in_array('quest_' . $quest['slug'], $claimed, true),
            ];
        }, self::QUESTS);
    }

    public function claimQuest(User $user, string $slug): array
    {
        $quest = collect(self::QUESTS)->firstWhere('slug', $slug);

        if (! $quest) {
            return ['valid' => false, 'message' => 'Quest tidak ditemukan'];
        }

        if (LoyaltyReward::where('user_id', $user->id)->where('reward_key', 'quest_' . $slug)->exists()) {
            return ['valid' => false, 'message' => 'Hadiah quest ini sudah diklaim'];
        }

        if (! $user->wallet) {
            return ['valid' => false, 'message' => 'Wallet tidak ditemukan'];
        }

        if (! $this->questCompleted($user, $slug)) {
            return ['valid' => false, 'message' => 'Syarat quest belum terpenuhi'];
        }

        $coins = (float) $quest['reward_coins'];

        $this->loyalty->earn($user, 'quest_' . $slug, $coins, 'Hadiah quest: ' . $quest['title']);

        return [
            'valid' => true,
            'coins' => $coins,
            'title' => $quest['title'],
        ];
    }

    private function questCompleted(User $user, string $slug): bool
    {
        return match ($slug) {
            'first_booking' => $this->completedBookingCount($user) >= 1,
            'five_bookings' => $this->completedBookingCount($user) >= 5,
            'give_review' => Review::where('user_id', $user->id)->exists(),
            'complete_profile' => (bool) ($user->name && $user->phone && $user->avatar),
            'claim_free_voucher' => VoucherClaim::where('user_id', $user->id)->where('source', 'free')->exists(),
            'exchange_voucher' => VoucherClaim::where('user_id', $user->id)->where('source', 'loyalty')->exists(),
            'first_topup' => TopUpTransaction::where('user_id', $user->id)->where('status', TopUpStatus::SUCCESS)->exists(),
            default => false,
        };
    }

    private function completedBookingCount(User $user): int
    {
        return Booking::where('user_id', $user->id)
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::REFUNDED])
            ->count();
    }
}