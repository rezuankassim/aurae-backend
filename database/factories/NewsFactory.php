<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement([0, 1]),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'is_published' => $this->faker->boolean(),
            'published_at' => $this->faker->optional()->dateTimeBetween('-1 years', 'now'),
        ];
    }
}
