<?php

namespace App\Lunar\Extensions;

use App\Lunar\Pages\ManageCollectionProducts;
use Lunar\Admin\Filament\Resources\CollectionResource\Pages\ManageCollectionAvailability;
use Lunar\Admin\Filament\Resources\CollectionResource\Pages\ManageCollectionChildren;
use Lunar\Admin\Filament\Resources\CollectionResource\Pages\ManageCollectionMedia;
use Lunar\Admin\Filament\Resources\CollectionResource\Pages\ManageCollectionProducts as BaseManageCollectionProducts;
use Lunar\Admin\Filament\Resources\CollectionResource\Pages\ManageCollectionUrls;
use Lunar\Admin\Support\Extending\ResourceExtension;

class CollectionResourceExtension extends ResourceExtension
{
    protected array $hiddenPages = [
        ManageCollectionChildren::class,
        ManageCollectionAvailability::class,
        ManageCollectionMedia::class,
        ManageCollectionUrls::class,
    ];

    protected array $hiddenPageKeys = [
        'children',
        'availability',
        'media',
        'urls',
    ];

    public function extendSubNavigation(array $pages): array
    {
        $filtered = array_map(
            fn ($page) => $page === BaseManageCollectionProducts::class ? ManageCollectionProducts::class : $page,
            $pages
        );

        return array_values(array_filter(
            $filtered,
            fn ($page) => ! in_array($page, $this->hiddenPages)
        ));
    }

    public function extendPages(array $pages): array
    {
        if (isset($pages['products'])) {
            $pages['products'] = ManageCollectionProducts::route('/{record}/products');
        }

        return array_filter(
            $pages,
            fn ($key) => ! in_array($key, $this->hiddenPageKeys),
            ARRAY_FILTER_USE_KEY
        );
    }
}
