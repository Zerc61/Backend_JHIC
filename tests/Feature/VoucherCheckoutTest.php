<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function userWithWallet(float $balance = 5000): User
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => $balance]);

        return $user->fresh();
    }

    private function makeVoucher(array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'code' => 'DISC' . strtoupper(substr(md5((string) mt_rand()), 0, 6)),
            'description' => 'Voucher diskon',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_purchase' => 0,
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addMonth(),
            'is_active' => true,
            'is_free' => true,
        ], $overrides));
    }

    private function claim(Voucher $voucher, User $user): VoucherClaim
    {
        return VoucherClaim::create([
            'user_id' => $user->id,
            'voucher_id' => $voucher->id,
            'source' => 'free',
            'status' => 'unused',
            'claimed_at' => Carbon::now(),
        ]);
    }

    private function destinationPayload(Destination $destination, array $extra = []): array
    {
        return array_merge([
            'booking_type' => 'destination_ticket',
            'destination_id' => $destination->id,
            'visit_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'number_of_visitors' => 2,
            'visitor_names' => ['Andi', 'Budi'],
            'contact_person' => 'Andi',
            'contact_phone' => '081234567890',
        ], $extra);
    }

    public function test_validate_rejects_voucher_user_has_not_claimed(): void
    {
        $user = $this->userWithWallet();
        $voucher = $this->makeVoucher();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/vouchers/validate', ['code' => $voucher->code, 'amount' => 100000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_validate_previews_discount_for_claimed_voucher(): void
    {
        $user = $this->userWithWallet();
        $voucher = $this->makeVoucher(['discount_type' => 'percentage', 'discount_value' => 10]);
        $this->claim($voucher, $user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/vouchers/validate', ['code' => $voucher->code, 'amount' => 100000])
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'voucher_code' => $voucher->code,
                'discount' => 10000,
                'original_amount' => 100000,
                'final_amount' => 90000,
            ]);
    }

    public function test_validate_rejects_already_used_claim(): void
    {
        $user = $this->userWithWallet();
        $voucher = $this->makeVoucher();
        $this->claim($voucher, $user)->update(['status' => 'used', 'used_at' => Carbon::now()]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/vouchers/validate', ['code' => $voucher->code, 'amount' => 100000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_booking_store_applies_voucher_and_marks_claim_used(): void
    {
        $user = $this->userWithWallet(500);
        $destination = Destination::factory()->create(['ticket_price' => 50000]);
        $voucher = $this->makeVoucher(['discount_type' => 'percentage', 'discount_value' => 10]);
        $this->claim($voucher, $user);

        $payload = $this->destinationPayload($destination, ['voucher_code' => $voucher->code]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bookings', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.total_price', 100000);
        $response->assertJsonPath('data.discount', 10000);
        $response->assertJsonPath('data.total_amount', 90000);
        $response->assertJsonPath('data.voucher_code', $voucher->code);
        // coin dihitung dari total setelah diskon
        $response->assertJsonPath('data.coin_amount', 45);

        $booking = $response->json('data');
        $this->assertDatabaseHas('bookings', [
            'id' => $booking['id'],
            'voucher_id' => $voucher->id,
            'discount' => 10000,
            'total_amount' => 90000,
        ]);
        $this->assertDatabaseHas('voucher_claims', [
            'user_id' => $user->id,
            'voucher_id' => $voucher->id,
            'status' => 'used',
        ]);
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'used_count' => 1,
        ]);
    }

    public function test_booking_store_rejects_unclaimed_voucher_code(): void
    {
        $user = $this->userWithWallet(500);
        $destination = Destination::factory()->create(['ticket_price' => 50000]);
        $voucher = $this->makeVoucher();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/bookings', $this->destinationPayload($destination, ['voucher_code' => $voucher->code]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('voucher_code')
            ->assertJsonMissingPath('data');

        $this->assertDatabaseCount('bookings', 0);
    }
}