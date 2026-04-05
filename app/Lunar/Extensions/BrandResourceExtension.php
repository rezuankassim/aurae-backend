<?php

namespace App\Lunar\Extensions;

use Lunar\Admin\Filament\Resources\BrandResource\Pages\ManageBrandCollections;
use Lunar\Admin\Filament\Resources\BrandResource\Pages\ManageBrandUrls;
use Lunar\Admin\Support\Extending\ResourceExtension;

class BrandResourceExtension extends ResourceExtension
{
    public function extendSubNavigation(array $pages): array
    {
        return array_values(array_filter(
            $pages,
            fn ($page) => ! in_array($page, [
                ManageBrandUrls::class,
                ManageBrandCollections::class,
            ])
        ));
    }
}
