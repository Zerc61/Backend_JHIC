<?php

namespace Tests\Feature;

use App\Enums\TopUpStatus;
use App\Models\TopUpTransaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Wallet::create(['user_id' => $this->user->id, 'balance' => 0]);
    }

    private function claimQuest(string $slug): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/quests/claim', ['slug' => $slug]);
    }

    private function makeVoucher(array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'code' => 'Q' . strtoupper(substr(md5((string) mt_rand()), 0, 6)),
            'description' => 'Voucher quest',
            'discount_type' => 'percentage',
            'discount_value' => 5,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'is_free' => true,
        ], $overrides));
    }

    public function test_index_lists_all_quests_uncompleted(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/quests');

        $response->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug')->all();

        $this->assertContains('first_booking', $slugs);
        $this->assertContains('five_bookings', $slugs);
        $this->assertContains('give_review', $slugs);
        $this->assertContains('complete_profile', $slugs);
        $this->assertContains('claim_free_voucher', $slugs);
        $this->assertContains('exchange_voucher', $slugs);
        $this->assertContains('first_topup', $slugs);

        foreach ($response->json('data') as $quest) {
            $this->assertFalse($quest['accomplished']);
            $this->assertFalse($quest['claimed']);
        }
    }

    public function test_cannot_claim_when_condition_not_met(): void
    {
        $this->claimQuest('give_review')
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_unknown_quest_slug_rejected(): void
    {
        $this->claimQuest('tidak_ada')
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_claim_complete_profile_reward_once(): void
    {
        $this->user->update(['name' => 'Ani', 'phone' => '0812345678', 'avatar' => 'avatar.png']);

        $response = $this->actingAs($this->user->fresh(), 'sanctum')->claimQuest('complete_profile');

        $response->assertOk();
        $response->assertJsonPath('data.coins', 30);
        $this->assertEquals(30, (float) $this->user->fresh()->wallet->balance);

        $this->assertDatabaseHas('loyalty_rewards', [
            'user_id' => $this->user->id,
            'reward_key' => 'quest_complete_profile',
        ]);

        // Sudah diklaim → ditolak
        $this->actingAs($this->user->fresh(), 'sanctum')
            ->postJson('/api/quests/claim', ['slug' => 'complete_profile'])
            ->assertStatus(422);
        $this->assertDatabaseCount('loyalty_rewards', 1);
    }

    public function test_claim_exchange_voucher_reward(): void
    {
        $voucher = $this->makeVoucher();
        VoucherClaim::create([
            'user_id' => $this->user->id,
            'voucher_id' => $voucher->id,
            'source' => 'loyalty',
            'status' => 'unused',
            'claimed_at' => now(),
        ]);

        $response = $this->claimQuest('exchange_voucher');

        $response->assertOk();
        $response->assertJsonPath('data.coins', 40);
        $this->assertEquals(40, (float) $this->user->fresh()->wallet->balance);
    }

    public function test_claim_first_topup_reward(): void
    {
        TopUpTransaction::create([
            'user_id' => $this->user->id,
            'amount_rupiah' => 100000,
            'rate_per_coin' => 500,
            'coins_received' => 200,
            'status' => TopUpStatus::SUCCESS,
            'paid_at' => now(),
            'expired_at' => now()->addDay(),
        ]);

        $response = $this->claimQuest('first_topup');

        $response->assertOk();
        $response->assertJsonPath('data.coins', 100);
        $this->assertEquals(100, (float) $this->user->fresh()->wallet->balance);
    }

    public function test_quest_completed_status_reflected_in_index(): void
    {
        $voucher = $this->makeVoucher();
        VoucherClaim::create([
            'user_id' => $this->user->id,
            'voucher_id' => $voucher->id,
            'source' => 'free',
            'status' => 'unused',
            'claimed_at' => now(),
        ]);

        $this->claimQuest('claim_free_voucher')->assertOk();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/quests');
        $quest = collect($response->json('data'))->firstWhere('slug', 'claim_free_voucher');

        $this->assertNotNull($quest);
        $this->assertTrue($quest['accomplished']);
        $this->assertTrue($quest['claimed']);
    }
}