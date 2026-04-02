<?php

namespace App\Lunar\Extensions;

use App\Lunar\Pages\EditShippingZonePage;
use App\Lunar\Pages\ManageShippingRatesPage;
use Lunar\Admin\Support\Extending\ResourceExtension;

class ShippingZoneResourceExtension extends ResourceExtension
{
    public function extendPages(array $pages): array
    {
        $pages['edit'] = EditShippingZonePage::route('/{record}/edit');
        $pages['rates'] = ManageShippingRatesPage::route('/{record}/rates');

        return $pages;
    }
}
