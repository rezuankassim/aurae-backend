<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Form;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductUrls;
use Lunar\Admin\Support\Extending\ResourceExtension;

class ProductResourceExtension extends ResourceExtension
{
    public function extendSubNavigation(array $pages): array
    {
        return array_values(array_filter(
            $pages,
            fn ($page) => $page !== ManageProductUrls::class
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
