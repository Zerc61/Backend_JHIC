<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVoucherControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN]);
    }

    private function touristUser(): User
    {
        return User::factory()->create(['role' => UserRole::TOURIST]);
    }

    private function voucherPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'EJT10',
            'description' => 'Diskon 10% booking',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'max_discount' => 100000,
            'min_purchase' => 200000,
            'total_quota' => 100,
            'per_user_limit' => 1,
            'valid_from' => now()->format('Y-m-d H:i:s'),
            'valid_until' => now()->addMonth()->format('Y-m-d H:i:s'),
            'is_active' => true,
            'loyalty_redeemable' => true,
            'cost_coins' => 100,
            'min_tier' => 'bronze',
            'is_free' => true,
        ], $overrides);
    }

    public function test_non_admin_cannot_access_voucher_admin(): void
    {
        $user = $this->touristUser();

        $this->actingAs($user, 'sanctum')->getJson('/api/admin/vouchers')->assertForbidden();
        $this->actingAs($user, 'sanctum')->postJson('/api/admin/vouchers', $this->voucherPayload())->assertForbidden();
    }

    public function test_admin_can_create_loyalty_redeemable_voucher(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->voucherPayload());

        $response->assertCreated()
            ->assertJsonPath('data.code', 'EJT10')
            ->assertJsonPath('data.conditions.cost_coins', 100)
            ->assertJsonPath('data.conditions.min_tier', 'bronze')
            ->assertJsonPath('data.is_free', true);

        $this->assertDatabaseHas('vouchers', ['code' => 'EJT10']);
    }

    public function test_voucher_code_is_uppercased_and_unique(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->voucherPayload(['code' => 'ejt10']))
            ->assertCreated();

        $this->assertDatabaseHas('vouchers', ['code' => 'EJT10']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->voucherPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_non_loyalty_voucher_has_null_conditions(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->voucherPayload(['loyalty_redeemable' => false]));

        $response->assertCreated()
            ->assertJsonPath('data.conditions', null)
            ->assertJsonPath('data.is_free', true);
    }

    public function test_admin_can_update_voucher(): void
    {
        $admin = $this->adminUser();
        $voucher = Voucher::factory()->create(['code' => 'OLDDISC']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/vouchers/{$voucher->id}", $this->voucherPayload([
                'code' => 'NEWDISC',
                'cost_coins' => 250,
                'min_tier' => 'gold',
            ]));

        $response->assertOk()
            ->assertJsonPath('data.code', 'NEWDISC')
            ->assertJsonPath('data.conditions.cost_coins', 250)
            ->assertJsonPath('data.conditions.min_tier', 'gold');
    }

    public function test_admin_can_delete_voucher(): void
    {
        $admin = $this->adminUser();
        $voucher = Voucher::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/vouchers/{$voucher->id}")
            ->assertOk();

        $this->assertSoftDeleted('vouchers', ['id' => $voucher->id]);
    }

    public function test_admin_can_filter_loyalty_vouchers_in_index(): void
    {
        $admin = $this->adminUser();
        Voucher::factory()->loyaltyRedeemable()->create();
        Voucher::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/vouchers?loyalty=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertNotNull($response->json('data.0.conditions'));
    }
}