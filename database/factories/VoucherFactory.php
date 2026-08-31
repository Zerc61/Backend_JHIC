<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('VCH-####')),
            'description' => fake()->sentence(4),
            'discount_type' => 'percentage',
            'discount_value' => fake()->randomElement([5, 10, 15, 20, 25]),
            'max_discount' => 100000,
            'min_purchase' => 200000,
            'total_quota' => 100,
            'used_count' => 0,
            'per_user_limit' => 1,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'applicable_to' => 'all',
            'applicable_items' => null,
            'conditions' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ]);
    }

    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function loyaltyRedeemable(int $costCoins = 100, string $minTier = 'bronze'): static
    {
        return $this->state(fn (array $attributes) => [
            'conditions' => [
                'cost_coins' => $costCoins,
                'min_tier' => $minTier,
            ],
        ]);
    }
}