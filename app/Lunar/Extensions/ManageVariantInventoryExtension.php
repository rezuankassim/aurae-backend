<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Form;
use Lunar\Admin\Support\Extending\EditPageExtension;

class ManageVariantInventoryExtension extends EditPageExtension
{
    public function extendForm(Form $form): Form
    {
        foreach ($form->getFlatComponents(withHidden: true) as $component) {
            if (method_exists($component, 'getStatePath')) {
                $statePath = $component->getStatePath();

                if (str_contains($statePath, 'backorder')) {
                    $component->hidden();
                }

                if (str_contains($statePath, 'purchasable')) {
                    $component->hidden();
                    $component->default('always');
                }
            }
        }

        return $form;
    }

    public function beforeSave(array $data): array
    {
        $data['backorder'] = 0;
        $data['purchasable'] = 'always';

        return $data;
    }
}
