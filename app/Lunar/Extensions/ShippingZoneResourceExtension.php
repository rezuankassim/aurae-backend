<?php

namespace App\Lunar\Extensions;

use App\Lunar\Pages\EditShippingZonePage;
use App\Lunar\Pages\ManageShippingRatesPage;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Lunar\Admin\Support\Extending\ResourceExtension;
use Lunar\Shipping\Filament\Resources\ShippingZoneResource\Pages\ManageShippingRates as BaseManageShippingRates;

class ShippingZoneResourceExtension extends ResourceExtension
{
    public function extendPages(array $pages): array
    {
        $pages['edit'] = EditShippingZonePage::route('/{record}/edit');

        // Keep BaseManageShippingRates as the registered page class so the sub-navigation
        // URL lookup (getResourcePageName) can still find it in getPages().
        // The route closure points to ManageShippingRatesPage so the custom
        // form/table is rendered when the user navigates to the rates page.
        $pages['rates'] = new PageRegistration(
            page: BaseManageShippingRates::class,
            route: fn (Panel $panel): Route => RouteFacade::get(
                '/{record}/rates',
                ManageShippingRatesPage::class
            )->middleware(ManageShippingRatesPage::getRouteMiddleware($panel))
             ->withoutMiddleware(ManageShippingRatesPage::getWithoutRouteMiddleware($panel)),
        );

        return $pages;
    }
}
