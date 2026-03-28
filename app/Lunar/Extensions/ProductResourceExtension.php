<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Form;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductAssociations;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductUrls;
use Lunar\Admin\Support\Extending\ResourceExtension;

class ProductResourceExtension extends ResourceExtension
{
    public function extendSubNavigation(array $pages): array
    {
        return array_values(array_filter(
            $pages,
            fn ($page) => ! in_array($page, [
                ManageProductUrls::class,
                ManageProductAssociations::class,
            ])
        ));
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
