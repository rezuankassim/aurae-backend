<?php

namespace App\Lunar\Extensions;

use Lunar\Admin\Support\Extending\ResourceExtension;
use Lunar\Shipping\Filament\Resources\ShippingMethodResource\Pages\ManageShippingMethodAvailability;

class ShippingMethodResourceExtension extends ResourceExtension
{
    public function extendSubNavigation(array $pages): array
    {
        return array_values(array_filter(
            $pages,
            fn ($page) => ! in_array($page, [
                ManageShippingMethodAvailability::class,
            ])
        ));
    }
}
