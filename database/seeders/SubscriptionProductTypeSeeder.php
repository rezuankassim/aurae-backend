<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\ProductType;

class SubscriptionProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create subscription product type if it doesn't exist
        ProductType::firstOrCreate(
            ['name' => 'Subscription Device'],
            ['is_subscription' => true]
        );

        // Also create a regular product type if needed
        ProductType::firstOrCreate(
            ['name' => 'Physical Product'],
            ['is_subscription' => false]
        );
    }
}
