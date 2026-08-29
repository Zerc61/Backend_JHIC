<?php

namespace Database\Factories;

use App\Enums\DestinationStatus;
use App\Models\Destination;
use App\Models\DestinationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Destination>
 */
class DestinationFactory extends Factory
{
    protected $model = Destination::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'destination_category_id' => DestinationCategory::factory(),
            'manager_id' => null,
            'name' => ucfirst($name),
            'slug' => str()->slug($name) . '-' . fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-8.9, -7.0),
            'longitude' => fake()->longitude(110.0, 114.5),
            'ticket_price' => fake()->randomElement([0, 10000, 25000, 50000]),
            'phone' => fake()->optional()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'status' => DestinationStatus::PUBLISHED,
        ];
    }
}
