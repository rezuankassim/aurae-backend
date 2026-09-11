<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UsageHistoryFactory extends Factory
{
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
