<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voucher;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyRewardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Wallet::create(['user_id' => $this->user->id, 'balance' => 0]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setDate(string $date): void
    {
        Carbon::setTestNow(Carbon::parse($date));
    }

    private function makeDay7Voucher(): Voucher
    {
        return Voucher::create([
            'code' => 'DAILY7',
            'description' => 'Voucher bonus hari ke-7',
            'discount_type' => 'percentage',
            'discount_value' => 5,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'is_free' => true,
        ]);
    }

    private function claim(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')->postJson('/api/daily/claim');
    }

    public function test_status_for_fresh_user(): void
    {
        $this->setDate('2026-08-01 09:00:00');

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/daily/status');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertFalse($data['claimed_today']);
        $this->assertSame(0, $data['streak_days']);
        $this->assertSame(1, $data['next_day']);
        $this->assertSame(10.0, $data['next_day_coins']);
        $this->assertFalse($data['next_day_voucher']);
        $this->assertSame(7, $data['cycle_days']);
        $this->assertSame([10, 15, 20, 25, 30, 40, 50], array_map('intval', $data['rewards']));
    }

    public function test_claim_first_day_grants_10_coins_once(): void
    {
        $this->setDate('2026-08-01 09:00:00');

        $response = $this->claim();

        $response->assertOk();
        $response->assertJsonPath('data.coins', 10);
        $response->assertJsonPath('data.day', 1);
        $response->assertJsonPath('data.voucher_code', null);
        $this->assertEquals(10, (float) $this->user->fresh()->wallet->balance);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/daily/claim')
            ->assertStatus(422)
            ->assertJsonValidationErrors('daily');

        $this->assertDatabaseHas('loyalty_rewards', [
            'user_id' => $this->user->id,
            'reward_key' => 'daily_2026-08-01',
        ]);
        $this->assertDatabaseCount('loyalty_rewards', 1);
    }

    public function test_seven_day_cycle_grants_voucher_then_resets(): void
    {
        $this->makeDay7Voucher();
        $coinsPerDay = [1 => 10, 2 => 15, 3 => 20, 4 => 25, 5 => 30, 6 => 40, 7 => 50, 8 => 10];

        foreach ($coinsPerDay as $day => $coins) {
            $this->setDate('2026-08-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT) . ' 09:00:00');
            $response = $this->claim();
            $response->assertOk();
            $this->assertSame($coins, (int) $response->json('data.coins'), "Hari ke-{$day}");
        }

        // 10+15+20+25+30+40+50+10 = 200
        $this->assertEquals(200, (float) $this->user->fresh()->wallet->balance);

        // Day 7 → voucher daily diklaim otomatis
        $this->assertDatabaseHas('voucher_claims', [
            'user_id' => $this->user->id,
            'source' => 'daily',
            'status' => 'unused',
        ]);

        // Hari ke-8 → siklus ulang ke reward hari ke-2
        $this->setDate('2026-08-08 09:00:00');
        $status = $this->actingAs($this->user, 'sanctum')->getJson('/api/daily/status');
        $status->assertOk();
        $this->assertSame(2, $status->json('data.next_day'));
        $this->assertSame(15.0, $status->json('data.next_day_coins'));
    }

    public function test_day7_reward_without_admin_voucher_degrades_to_coins_only(): void
    {
        // Tanpa voucher DAILY7, hari ke-7 tetap memberi coin tanpa error.
        foreach ([1, 2, 3, 4, 5, 6, 7] as $day) {
            $this->setDate('2026-08-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT) . ' 09:00:00');
            $this->claim()->assertOk();
        }

        $this->assertDatabaseMissing('voucher_claims', ['user_id' => $this->user->id, 'source' => 'daily']);
        $this->assertEquals(190, (float) $this->user->fresh()->wallet->balance);
    }
}