<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserSubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'subscription_id' => \App\Models\Subscription::factory(),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'transaction_id' => 'TXN-'.$this->faker->uuid(),
            'payment_method' => 'senangpay',
            'payment_status' => 'completed',
            'paid_at' => now(),
        ];
    }
}
