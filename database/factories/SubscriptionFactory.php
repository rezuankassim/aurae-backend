<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'icon' => 'default-icon.png',
            'title' => $this->faker->words(3, true),
            'pricing_title' => $this->faker->currencyCode() . ' ' . $this->faker->randomNumber(3),
            'description' => $this->faker->sentence(),
            'max_machines' => $this->faker->randomElement([1, 2, 5, 10]),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'is_active' => true,
        ];
    }
}
