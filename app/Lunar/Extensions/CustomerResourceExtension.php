<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

class CustomerResourceExtension extends ResourceExtension
{
    public function extendTable(Table $table): Table
    {
        $columns = array_values(array_filter(
            $table->getColumns(),
            fn ($column) => $column->getName() !== 'customerGroups.name'
        ));

        $filters = array_values(array_filter(
            $table->getFilters(),
            fn ($filter) => $filter->getName() !== 'customer_group'
        ));

        return $table->columns($columns)->filters($filters)->bulkActions([]);
    }

    public function extendForm(Form $form): Form
    {
        foreach ($form->getFlatComponents(withHidden: true) as $component) {
            if (method_exists($component, 'getName') && $component->getName() === 'customerGroups') {
                $component->hidden();
            }
        }

        return $form;
    }
}
