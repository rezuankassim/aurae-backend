<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HealthReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_body_file' => $this->faker->optional()->randomElement([null, 'health-reports/'.$this->faker->uuid.'.pdf']),
            'meridian_file' => $this->faker->optional()->randomElement([null, 'health-reports/'.$this->faker->uuid.'.pdf']),
            'multidimensional_file' => $this->faker->optional()->randomElement([null, 'health-reports/'.$this->faker->uuid.'.pdf']),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
