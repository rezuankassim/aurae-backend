<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeviceMaintenance>
 */
class DeviceMaintenanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => $this->faker->numberBetween(0, 3),
            'user_id' => \App\Models\User::factory(),
            'maintenance_requested_at' => $this->faker->dateTimeThisYear(),
            'factory_maintenance_requested_at' => $this->faker->optional()->dateTimeThisYear(),
        ];
    }
}
