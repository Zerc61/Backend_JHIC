<?php

namespace Database\Factories;

use App\Enums\UmkmStatus;
use App\Models\Destination;
use App\Models\Umkm;
use App\Models\UmkmCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Umkm>
 */
class UmkmFactory extends Factory
{
    protected $model = Umkm::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'user_id' => User::factory()->umkm(),
            'destination_id' => Destination::factory(),
            'umkm_category_id' => UmkmCategory::factory(),
            'name' => $name,
            'slug' => str()->slug($name) . '-' . fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-8.9, -7.0),
            'longitude' => fake()->longitude(110.0, 114.5),
            'phone' => fake()->phoneNumber(),
            'opening_hours' => '08:00 - 17:00',
            'status' => UmkmStatus::ACTIVE,
        ];
    }
}
