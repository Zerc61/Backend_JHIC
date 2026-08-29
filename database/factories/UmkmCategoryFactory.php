<?php

namespace Database\Factories;

use App\Models\UmkmCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UmkmCategory>
 */
class UmkmCategoryFactory extends Factory
{
    protected $model = UmkmCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => str()->slug($name) . '-' . fake()->unique()->numberBetween(1, 999999),
            'icon' => fake()->optional()->randomElement(['food', 'craft', 'souvenir', 'cafe']),
        ];
    }
}
