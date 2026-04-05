<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Lunar\Admin\Support\Extending\EditPageExtension;

class ShippingMethodEditExtension extends EditPageExtension
{
    public function extendForm(Form $form): Form
    {
        $schema = $form->getComponents();

        // Find and modify the driver select field to include all shipping driver options
        $this->modifyDriverSelect($schema);

        // Replace the RichEditor description field with a plain Textarea
        $this->replaceDescriptionWithTextarea($schema);

        // Hide the cutoff and stock_available fields
        $this->hideFields($schema, ['cutoff', 'stock_available']);

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

    protected function replaceDescriptionWithTextarea(array &$components): void
    {
        foreach ($components as $key => $component) {
            if ($component instanceof RichEditor && $component->getName() === 'description') {
                $components[$key] = Textarea::make('description')
                    ->label(__('lunarpanel.shipping::shippingmethod.form.description.label'));

                return;
            }

            // Recursively search child components and set them back on the parent
            if (method_exists($component, 'getChildComponents') && method_exists($component, 'schema')) {
                $childComponents = $component->getChildComponents();
                $this->replaceDescriptionWithTextarea($childComponents);
                $component->schema($childComponents);
            }
        }
    }

    protected function hideFields(array &$components, array $fieldNames): void
    {
        foreach ($components as $component) {
            if (method_exists($component, 'getName') && in_array($component->getName(), $fieldNames)) {
                $component->hidden();
            }

            // Recursively search child components and set them back on the parent
            if (method_exists($component, 'getChildComponents') && method_exists($component, 'schema')) {
                $childComponents = $component->getChildComponents();
                $this->hideFields($childComponents, $fieldNames);
                $component->schema($childComponents);
            }
        }
    }
}
