<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement([0, 1, 2]),
            'name' => fake()->name(),
            'line1' => fake()->streetName(),
            'line2' => fake()->streetAddress(),
            'line3' => null,
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
        ];
    }
}
