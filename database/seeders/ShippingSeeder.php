<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Price;
use Lunar\Shipping\Models\ShippingMethod;
use Lunar\Shipping\Models\ShippingRate;
use Lunar\Shipping\Models\ShippingZone;

class ShippingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a shipping zone for Malaysia
        $zone = ShippingZone::firstOrCreate(
            ['name' => 'Malaysia'],
            ['type' => 'countries']
        );

        // Attach Malaysia country to the zone
        $malaysia = Country::where('iso2', 'MY')->first();
        if ($malaysia && ! $zone->countries()->where('country_id', $malaysia->id)->exists()) {
            $zone->countries()->attach($malaysia->id);
        }

        // Get default currency
        $currency = Currency::getDefault();

        // Get default customer group
        $customerGroup = CustomerGroup::getDefault();

        // Create Basic Delivery shipping method
        $basicDelivery = ShippingMethod::updateOrCreate(
            ['code' => 'BASDEL'],
            [
                'name' => 'Basic Delivery',
                'description' => 'Standard delivery to your address',
                'driver' => 'flat-rate',
                'enabled' => true,
                'stock_available' => false,
            ]
        );

        // Attach customer group to shipping method
        if ($customerGroup && ! $basicDelivery->customerGroups()->where('customer_group_id', $customerGroup->id)->exists()) {
            $basicDelivery->customerGroups()->attach($customerGroup->id, [
                'visible' => true,
                'enabled' => true,
            ]);
        }

        // Create shipping rate for Basic Delivery
        $basicDeliveryRate = ShippingRate::firstOrCreate(
            [
                'shipping_method_id' => $basicDelivery->id,
                'shipping_zone_id' => $zone->id,
            ],
            ['enabled' => true]
        );

        // Create price for Basic Delivery (RM 5.00 = 500 cents)
        if (! $basicDeliveryRate->prices()->where('currency_id', $currency->id)->exists()) {
            Price::create([
                'priceable_type' => ShippingRate::class,
                'priceable_id' => $basicDeliveryRate->id,
                'currency_id' => $currency->id,
                'price' => 500, // RM 5.00 in cents
                'min_quantity' => 1,
            ]);
        }

        // Create Pick Up shipping method
        $pickup = ShippingMethod::updateOrCreate(
            ['code' => 'PICKUP'],
            [
                'name' => 'Pick up in store',
                'description' => 'Pick your order up in store',
                'driver' => 'collection',
                'enabled' => true,
                'stock_available' => false,
            ]
        );

        // Attach customer group to pickup method
        if ($customerGroup && ! $pickup->customerGroups()->where('customer_group_id', $customerGroup->id)->exists()) {
            $pickup->customerGroups()->attach($customerGroup->id, [
                'visible' => true,
                'enabled' => true,
            ]);
        }

        // Create shipping rate for Pick Up
        $pickupRate = ShippingRate::firstOrCreate(
            [
                'shipping_method_id' => $pickup->id,
                'shipping_zone_id' => $zone->id,
            ],
            ['enabled' => true]
        );

        // Collection driver uses free shipping, but we still need a price entry
        // Create price for Pick Up (RM 0.00)
        if (! $pickupRate->prices()->where('currency_id', $currency->id)->exists()) {
            Price::create([
                'priceable_type' => ShippingRate::class,
                'priceable_id' => $pickupRate->id,
                'currency_id' => $currency->id,
                'price' => 0,
                'min_quantity' => 1,
            ]);
        }

        $this->command->info('Shipping methods seeded successfully!');
        $this->command->info('- BASDEL (Basic Delivery): RM 5.00');
        $this->command->info('- PICKUP (Pick up in store): Free');
    }
}
