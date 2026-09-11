<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\ProductType;

class SubscriptionProductTypeSeeder extends Seeder
{
    public function run(): void
    {

        ProductType::firstOrCreate(
            ['name' => 'Subscription Device'],
            ['is_subscription' => true]
        );

        ProductType::firstOrCreate(
            ['name' => 'Physical Product'],
            ['is_subscription' => false]
        );
    }
}
