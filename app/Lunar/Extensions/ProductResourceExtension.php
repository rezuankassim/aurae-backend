<?php

namespace App\Lunar\Extensions;

use App\Lunar\Pages\ManageProductCollections;
use Filament\Forms\Form;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductAssociations;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductAvailability;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductCollections as BaseManageProductCollections;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductUrls;
use Lunar\Admin\Support\Extending\ResourceExtension;

class ProductResourceExtension extends ResourceExtension
{
    public function extendSubNavigation(array $pages): array
    {
        $pages = array_map(
            fn ($page) => $page === BaseManageProductCollections::class
                ? ManageProductCollections::class
                : $page,
            $pages,
        );

        return array_values(array_filter(
            $pages,
            fn ($page) => ! in_array($page, [
                ManageProductAvailability::class,
                ManageProductUrls::class,
                ManageProductAssociations::class,
            ])
        ));
    }

    public function extendPages(array $pages): array
    {
        $pages['collections'] = ManageProductCollections::route('/{record}/collections');

        return $pages;
    }

    public function extendForm(Form $form): Form
    {
        foreach ($form->getFlatComponents(withHidden: true) as $component) {
            if (method_exists($component, 'getName') && $component->getName() === 'tags') {
                $component->hidden();
            }
        }

        return $form;
    }
}
