<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Models\Wallet;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherClaimTest extends TestCase
{
    use RefreshDatabase;

    private function userWithWallet(float $balance = 0): User
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => $balance]);

        return $user->fresh();
    }

    private function makeVoucher(array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'code' => 'FREE' . strtoupper(substr(md5((string) mt_rand()), 0, 6)),
            'description' => 'Voucher gratis',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_purchase' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'is_free' => true,
        ], $overrides));
    }

    public function test_guest_cannot_claim_voucher(): void
    {
        $voucher = $this->makeVoucher();

        $this->postJson("/api/vouchers/{$voucher->id}/claim")->assertUnauthorized();
    }

    public function test_user_can_claim_free_voucher_once(): void
    {
        $user = $this->userWithWallet();
        $voucher = $this->makeVoucher();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/vouchers/{$voucher->id}/claim");

        $response->assertCreated();
        $response->assertJsonPath('data.voucher.code', $voucher->code);
        $this->assertDatabaseHas('voucher_claims', [
            'user_id' => $user->id,
            'voucher_id' => $voucher->id,
            'source' => 'free',
            'status' => 'unused',
        ]);
    }

    public function test_user_cannot_claim_same_voucher_twice(): void
    {
        $user = $this->userWithWallet();
        $voucher = $this->makeVoucher();

        $this->actingAs($user, 'sanctum')->postJson("/api/vouchers/{$voucher->id}/claim")->assertCreated();
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/vouchers/{$voucher->id}/claim")
            ->assertStatus(422)
            ->assertJsonValidationErrors('voucher_id');

        $this->assertDatabaseCount('voucher_claims', 1);
    }

    public function test_user_cannot_claim_non_free_voucher(): void
    {
        $user = $this->userWithWallet();
        $voucher = $this->makeVoucher(['is_free' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/vouchers/{$voucher->id}/claim")
            ->assertStatus(422)
            ->assertJsonValidationErrors('voucher_id');
    }

    public function test_user_cannot_claim_inactive_voucher(): void
    {
        $user = $this->userWithWallet();
        $voucher = $this->makeVoucher(['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/vouchers/{$voucher->id}/claim")
            ->assertStatus(422)
            ->assertJsonValidationErrors('voucher_id');
    }

    public function test_user_cannot_claim_when_quota_exhausted(): void
    {
        $user = $this->userWithWallet();
        $voucher = $this->makeVoucher(['total_quota' => 1, 'used_count' => 1]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/vouchers/{$voucher->id}/claim")
            ->assertStatus(422)
            ->assertJsonValidationErrors('voucher_id');
    }

    public function test_free_list_only_shows_free_active_vouchers_with_quota(): void
    {
        $this->makeVoucher();
        $this->makeVoucher(['is_free' => false]);
        $this->makeVoucher(['is_active' => false]);
        $this->makeVoucher(['total_quota' => 1, 'used_count' => 1]);

        $response = $this->getJson('/api/vouchers/free');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertTrue($response->json('data.0.is_free'));
    }

    public function test_my_vouchers_lists_free_and_loyalty_claims(): void
    {
        $user = $this->userWithWallet(600);
        app(LoyaltyService::class)->earn($user, 'upgrade', 500, 'Promosi');
        $free = $this->makeVoucher();
        $paid = Voucher::create([
            'code' => 'PAY' . strtoupper(substr(md5((string) mt_rand()), 0, 6)),
            'description' => 'Voucher loyalty',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'is_free' => false,
            'conditions' => ['cost_coins' => 100, 'min_tier' => 'silver'],
        ]);

        $this->actingAs($user, 'sanctum')->postJson("/api/vouchers/{$free->id}/claim")->assertCreated();
        app(LoyaltyService::class)->redeemVoucher($user, $paid->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/my-vouchers');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $sources = collect($response->json('data'))->pluck('source')->sort()->values();
        $this->assertEquals(['free', 'loyalty'], $sources->all());

        $this->assertDatabaseHas('voucher_claims', [
            'user_id' => $user->id,
            'voucher_id' => $paid->id,
            'source' => 'loyalty',
        ]);
    }
}