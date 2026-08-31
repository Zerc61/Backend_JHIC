<?php

namespace Tests\Feature;

use App\Enums\CoinTransactionType;
use App\Models\CoinTransaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Wallet;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    private function userWithWallet(float $balance = 0): User
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => $balance]);

        return $user->fresh();
    }

    private function makeVoucher(array $conditions): Voucher
    {
        return Voucher::create([
            'code' => 'LOY' . strtoupper(substr(md5((string) mt_rand()), 0, 6)),
            'description' => 'Diskon voucher test',
            'discount_type' => 'nominal',
            'discount_value' => 10000,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'conditions' => $conditions,
        ]);
    }

    public function test_earn_is_idempotent_per_key(): void
    {
        $user = $this->userWithWallet();
        $service = app(LoyaltyService::class);

        $service->earn($user, 'daily_login_2026-01-01', 10, 'Login harian');
        $service->earn($user, 'daily_login_2026-01-01', 10, 'Login harian');

        $this->assertEquals(10, (float) $user->fresh()->wallet->balance);
        $this->assertEquals(1, CoinTransaction::where('type', CoinTransactionType::EARN)->count());
        $this->assertDatabaseCount('loyalty_rewards', 1);
    }

    public function test_total_earned_and_tier_promotion(): void
    {
        $user = $this->userWithWallet();
        $service = app(LoyaltyService::class);

        $service->earn($user, 'a', 300, 'A');
        $service->earn($user, 'b', 200, 'B');

        $this->assertEquals('silver', $user->fresh()->loyalty_tier);
        $this->assertEquals('gold', $service->tierFor(1500));
        $this->assertEquals('platinum', $service->tierFor(3000));
    }

    public function test_earn_returns_null_when_user_has_no_wallet(): void
    {
        $user = User::factory()->create();

        $this->assertNull(app(LoyaltyService::class)->earn($user, 'k', 10, 'X'));
    }

    public function test_daily_login_reward_once_per_day(): void
    {
        $user = $this->userWithWallet();
        $service = app(LoyaltyService::class);

        $service->rewardDailyLogin($user);
        $service->rewardDailyLogin($user);

        $this->assertEquals(10, (float) $user->fresh()->wallet->balance);
        $this->assertDatabaseCount('loyalty_rewards', 1);
    }

    public function test_expire_coins_reduces_balance_and_records_expiry(): void
    {
        $user = $this->userWithWallet(100);
        $tx = CoinTransaction::create([
            'wallet_id' => $user->wallet->id,
            'type' => CoinTransactionType::EARN,
            'amount' => 40,
            'balance_before' => 60,
            'balance_after' => 100,
            'description' => 'Bonus lama',
            'expires_at' => now()->subDay(),
            'is_expired' => false,
        ]);

        $count = app(LoyaltyService::class)->expireCoins();

        $this->assertEquals(1, $count);
        $this->assertEquals(60, (float) $user->fresh()->wallet->balance);
        $this->assertTrue((bool) $tx->fresh()->is_expired);
        $this->assertDatabaseHas('coin_transactions', [
            'wallet_id' => $user->wallet->id,
            'type' => CoinTransactionType::EXPIRE,
            'amount' => 40,
        ]);
    }

    public function test_redeem_voucher_requires_minimum_tier(): void
    {
        $user = $this->userWithWallet(600);
        $voucher = $this->makeVoucher(['min_tier' => 'gold', 'cost_coins' => 100]);

        $result = app(LoyaltyService::class)->redeemVoucher($user, $voucher->id);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('tier gold', $result['message']);
    }

    public function test_redeem_voucher_success_and_duplicate_rejected(): void
    {
        $user = $this->userWithWallet(600);
        $service = app(LoyaltyService::class);
        $voucher = $this->makeVoucher(['min_tier' => 'silver', 'cost_coins' => 100]);

        $service->earn($user, 'upgrade', 500, 'Promosi');
        $first = $service->redeemVoucher($user, $voucher->id);

        $this->assertTrue($first['valid']);
        $this->assertEquals(1000, (float) $user->fresh()->wallet->balance);

        $second = $service->redeemVoucher($user, $voucher->id);
        $this->assertFalse($second['valid']);
        $this->assertDatabaseHas('coin_transactions', [
            'wallet_id' => $user->wallet->id,
            'type' => CoinTransactionType::REDEEM,
            'amount' => 100,
        ]);
    }

    public function test_redeem_voucher_uses_the_users_wallet_not_the_first_wallet(): void
    {
        // Regresi: `$wallet->lockForUpdate()->first()` tanpa scope where dulu mengambil
        // wallet PERTAMA di tabel (saldo kecil), bukan wallet user → selalu "coin tidak cukup".
        $earlierPoor = $this->userWithWallet(0);
        $user = $this->userWithWallet(600);
        $service = app(LoyaltyService::class);
        $voucher = $this->makeVoucher(['min_tier' => 'bronze', 'cost_coins' => 100]);

        $service->earn($user, 'upgrade', 500, 'Promosi');
        $result = $service->redeemVoucher($user, $voucher->id);

        $this->assertTrue($result['valid']);
        $this->assertEquals(1000, (float) $user->fresh()->wallet->balance);
        $this->assertEquals(0, (float) $earlierPoor->fresh()->wallet->balance);
        $this->assertDatabaseHas('coin_transactions', [
            'wallet_id' => $user->wallet->id,
            'type' => CoinTransactionType::REDEEM,
            'amount' => 100,
        ]);
    }

    public function test_referral_reward_given_once_per_pair(): void
    {
        $referrer = $this->userWithWallet();
        $referee = User::factory()->create();
        $service = app(LoyaltyService::class);

        $service->rewardReferral($referrer, $referee);
        $service->rewardReferral($referrer, $referee);

        $this->assertEquals(500, (float) $referrer->fresh()->wallet->balance);
        $this->assertDatabaseCount('loyalty_rewards', 1);
    }
}