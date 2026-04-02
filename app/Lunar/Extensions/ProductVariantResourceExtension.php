<?php

namespace App\Lunar\Extensions;

use App\Lunar\Pages\ManageVariantPricingPage;
use Lunar\Admin\Filament\Resources\ProductVariantResource\Pages\ManageVariantPricing;
use Lunar\Admin\Support\Extending\ResourceExtension;

class ProductVariantResourceExtension extends ResourceExtension
{
    public function extendSubNavigation(array $pages): array
    {
        return array_map(
            fn ($page) => $page === ManageVariantPricing::class
                ? ManageVariantPricingPage::class
                : $page,
            $pages,
        );
    }

    public function extendPages(array $pages): array
    {
        $pages['pricing'] = ManageVariantPricingPage::route('/{record}/pricing');

        return $pages;
    }
}
