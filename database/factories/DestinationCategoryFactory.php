<?php

namespace Database\Factories;

use App\Models\DestinationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DestinationCategory>
 */
class DestinationCategoryFactory extends Factory
{
    protected $model = DestinationCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => str()->slug($name) . '-' . fake()->unique()->numberBetween(1, 999999),
            'icon' => fake()->optional()->randomElement(['mountain', 'beach', 'city', 'temple']),
        ];
    }
}
