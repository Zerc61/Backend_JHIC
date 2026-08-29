<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Umkm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'umkm_id' => Umkm::factory(),
            'name' => ucwords($name),
            'slug' => str()->slug($name) . '-' . fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(5, 100) * 1000,
            'stock' => fake()->numberBetween(1, 100),
            'unit' => fake()->randomElement(['pcs', 'porsi', 'pack', 'buah']),
            'image' => null,
            'status' => ProductStatus::AVAILABLE,
        ];
    }
}
