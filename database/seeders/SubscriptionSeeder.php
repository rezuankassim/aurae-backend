<?php

namespace Database\Seeders;

use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscriptions = [
            [
                'title' => 'Basic Plan',
                'pricing_title' => 'RM 49.90 / month',
                'description' => 'Perfect for individuals just starting out. Includes 1 device.',
                'max_devices' => 1,
                'price' => 49.90,
                'is_active' => true,
            ],
            [
                'title' => 'Family Plan',
                'pricing_title' => 'RM 99.90 / month',
                'description' => 'Great for small families. Connect up to 3 devices and share the wellness experience.',
                'max_devices' => 3,
                'price' => 99.90,
                'is_active' => true,
            ],
            [
                'title' => 'Premium Plan',
                'pricing_title' => 'RM 149.90 / month',
                'description' => 'For power users and large families. Enjoy unlimited access with up to 5 devices.',
                'max_devices' => 5,
                'price' => 149.90,
                'is_active' => true,
            ],
            [
                'title' => 'Enterprise Plan',
                'pricing_title' => 'RM 299.90 / month',
                'description' => 'Perfect for wellness centers and businesses. Support up to 10 devices with priority support.',
                'max_devices' => 10,
                'price' => 299.90,
                'is_active' => true,
            ],
        ];

        foreach ($subscriptions as $subscription) {
            Subscription::create($subscription);
        }
    }
}
