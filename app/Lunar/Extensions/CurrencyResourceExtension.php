<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

class CurrencyResourceExtension extends ResourceExtension
{
    public function extendTable(Table $table): Table
    {
        $columns = array_values(array_filter(
            $table->getColumns(),
            fn ($column) => $column->getName() !== 'sync_prices'
        ));

        return $table->columns($columns);
    }

    public function extendForm(Form $form): Form
    {
        foreach ($form->getFlatComponents(withHidden: true) as $component) {
            if (method_exists($component, 'getName') && $component->getName() === 'sync_prices') {
                $component->hidden();
            }
        }

        return $form;
    }
}
