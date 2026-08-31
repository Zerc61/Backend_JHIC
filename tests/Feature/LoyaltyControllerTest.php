<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voucher;
use App\Models\Wallet;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithWallet(float $balance = 0): User
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => $balance]);

        return $user->fresh();
    }

    private function makeVoucher(array $conditions, bool $withConditions = true): Voucher
    {
        return Voucher::create([
            'code' => 'RED' . strtoupper(substr(md5((string) mt_rand()), 0, 6)),
            'description' => 'Voucher redemption',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'conditions' => $withConditions ? $conditions : null,
        ]);
    }

    public function test_status_returns_tier_balance_and_referral_code(): void
    {
        $user = $this->createUserWithWallet(25);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/loyalty/status');

        $response->assertOk();
        $response->assertJsonPath('data.tier', 'bronze');
        $response->assertJsonPath('data.balance', 25);
        $response->assertJsonPath('data.referral_code', $user->fresh()->referral_code);
        $this->assertNotNull($user->fresh()->referral_code);
    }

    public function test_status_only_lists_active_vouchers_with_redeem_conditions(): void
    {
        $user = $this->createUserWithWallet();
        $this->makeVoucher(['min_tier' => 'bronze', 'cost_coins' => 50]);
        $this->makeVoucher([], false);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/loyalty/status');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.redeemable_vouchers');
    }

    public function test_redeem_voucher_success_deducts_balance(): void
    {
        $user = $this->createUserWithWallet(600);
        app(LoyaltyService::class)->earn($user, 'upgrade', 500, 'Promosi');
        $voucher = $this->makeVoucher(['min_tier' => 'silver', 'cost_coins' => 100]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/loyalty/redeem-voucher', ['voucher_id' => $voucher->id]);

        $response->assertOk();
        $response->assertJsonPath('data.code', $voucher->code);
        $this->assertEquals(1000, (float) $user->fresh()->wallet->balance);
    }

    public function test_redeem_voucher_rejected_when_redeemed_twice(): void
    {
        $user = $this->createUserWithWallet(600);
        app(LoyaltyService::class)->earn($user, 'upgrade', 500, 'Promosi');
        $voucher = $this->makeVoucher(['min_tier' => 'silver', 'cost_coins' => 100]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/loyalty/redeem-voucher', ['voucher_id' => $voucher->id])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/loyalty/redeem-voucher', ['voucher_id' => $voucher->id])
            ->assertStatus(422);
    }

    public function test_redeem_voucher_rejected_when_tier_too_low(): void
    {
        $user = $this->createUserWithWallet(600);
        $voucher = $this->makeVoucher(['min_tier' => 'gold', 'cost_coins' => 100]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/loyalty/redeem-voucher', ['voucher_id' => $voucher->id]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('voucher_id');
    }

    public function test_status_excludes_already_redeemed_vouchers(): void
    {
        $user = $this->createUserWithWallet(600);
        app(LoyaltyService::class)->earn($user, 'upgrade', 500, 'Promosi');
        $voucher = $this->makeVoucher(['min_tier' => 'bronze', 'cost_coins' => 100]);
        app(LoyaltyService::class)->redeemVoucher($user, $voucher->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/loyalty/status');

        $response->assertOk();
        $response->assertJsonCount(0, 'data.redeemable_vouchers');
    }

    public function test_history_lists_redeem_and_earn_transactions(): void
    {
        $user = $this->createUserWithWallet(600);
        $service = app(LoyaltyService::class);
        $service->earn($user, 'upgrade', 500, 'Promosi');
        $voucher = $this->makeVoucher(['min_tier' => 'silver', 'cost_coins' => 100]);
        $service->redeemVoucher($user, $voucher->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/loyalty/history');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }
}