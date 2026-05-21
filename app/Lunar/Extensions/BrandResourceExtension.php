<?php

namespace App\Lunar\Extensions;

use App\Lunar\Pages\ManageBrandProducts;
use Lunar\Admin\Filament\Resources\BrandResource\Pages\ManageBrandCollections;
use Lunar\Admin\Filament\Resources\BrandResource\Pages\ManageBrandProducts as BaseManageBrandProducts;
use Lunar\Admin\Filament\Resources\BrandResource\Pages\ManageBrandUrls;
use Lunar\Admin\Support\Extending\ResourceExtension;

class BrandResourceExtension extends ResourceExtension
{
    public function extendSubNavigation(array $pages): array
    {
        $pages = array_map(
            fn ($page) => match ($page) {
                BaseManageBrandProducts::class => ManageBrandProducts::class,
                default => $page,
            },
            $pages,
        );

        return array_values(array_filter(
            $pages,
            fn ($page) => ! in_array($page, [
                ManageBrandUrls::class,
                ManageBrandCollections::class,
            ])
        ));
    }

    public function extendPages(array $pages): array
    {
        $pages['products'] = ManageBrandProducts::route('/{record}/products');

        return $pages;
    }
}
