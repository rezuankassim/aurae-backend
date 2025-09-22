<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\News>
 */
class NewsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement([0, 1]), // 0: News, 1: Promotion
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'is_published' => $this->faker->boolean(),
            'published_at' => $this->faker->optional()->dateTimeBetween('-1 years', 'now'),
        ];
    }
}
