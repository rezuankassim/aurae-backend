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
    public function run(): void
    {

        $zone = ShippingZone::firstOrCreate(
            ['name' => 'Malaysia'],
            ['type' => 'countries']
        );

        $malaysia = Country::where('iso2', 'MY')->first();
        if ($malaysia && ! $zone->countries()->where('country_id', $malaysia->id)->exists()) {
            $zone->countries()->attach($malaysia->id);
        }

        $currency = Currency::getDefault();

        $customerGroup = CustomerGroup::getDefault();

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

        if ($customerGroup && ! $basicDelivery->customerGroups()->where('customer_group_id', $customerGroup->id)->exists()) {
            $basicDelivery->customerGroups()->attach($customerGroup->id, [
                'visible' => true,
                'enabled' => true,
            ]);
        }

        $basicDeliveryRate = ShippingRate::firstOrCreate(
            [
                'shipping_method_id' => $basicDelivery->id,
                'shipping_zone_id' => $zone->id,
            ],
            ['enabled' => true]
        );

        if (! $basicDeliveryRate->prices()->where('currency_id', $currency->id)->exists()) {
            Price::create([
                'priceable_type' => ShippingRate::class,
                'priceable_id' => $basicDeliveryRate->id,
                'currency_id' => $currency->id,
                'price' => 500,
                'min_quantity' => 1,
            ]);
        }

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

        if ($customerGroup && ! $pickup->customerGroups()->where('customer_group_id', $customerGroup->id)->exists()) {
            $pickup->customerGroups()->attach($customerGroup->id, [
                'visible' => true,
                'enabled' => true,
            ]);
        }

        $pickupRate = ShippingRate::firstOrCreate(
            [
                'shipping_method_id' => $pickup->id,
                'shipping_zone_id' => $zone->id,
            ],
            ['enabled' => true]
        );

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
