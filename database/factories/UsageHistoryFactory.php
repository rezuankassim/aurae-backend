<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsageHistory>
 */
class UsageHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => [
                'action' => $this->faker->randomElement(['login', 'logout', 'viewed_page', 'updated_profile']),
                'timestamp' => $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s'),
                'details' => $this->faker->sentence,
            ],
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
