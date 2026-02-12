<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Lunar\Admin\Support\Extending\EditPageExtension;

class ShippingMethodEditExtension extends EditPageExtension
{
    public function extendForm(Form $form): Form
    {
        $schema = $form->getComponents();

        // Find and modify the driver select field to include all shipping driver options
        $this->modifyDriverSelect($schema);

        return $form->schema($schema);
    }

    protected function modifyDriverSelect(array &$components): void
    {
        foreach ($components as $component) {
            // Check if this is the driver select
            if ($component instanceof Select && $component->getName() === 'driver') {
                $component->options([
                    'ship-by' => __('lunarpanel.shipping::shippingmethod.form.driver.options.ship-by'),
                    'collection' => __('lunarpanel.shipping::shippingmethod.form.driver.options.collection'),
                    'flat-rate' => __('lunarpanel.shipping::shippingmethod.form.driver.options.flat-rate'),
                    'free-shipping' => __('lunarpanel.shipping::shippingmethod.form.driver.options.free-shipping'),
                ]);

                return;
            }

            // Recursively search child components
            if (method_exists($component, 'getChildComponents')) {
                $childComponents = $component->getChildComponents();
                $this->modifyDriverSelect($childComponents);
            }
        }
    }
}
