<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Form;
use Lunar\Admin\Support\Extending\EditPageExtension;

class ManageVariantPricingExtension extends EditPageExtension
{
    public function extendForm(Form $form): Form
    {
        foreach ($form->getFlatComponents(withHidden: true) as $component) {
            if (method_exists($component, 'getStatePath')) {
                $statePath = $component->getStatePath();

                if (str_contains($statePath, 'compare_price')) {
                    $component->hidden();
                }

                if (str_contains($statePath, '.value') && str_contains($statePath, 'basePrices')) {
                    $component->helperText(null);
                }
            }
        }

        return $form;
    }
}
