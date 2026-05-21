<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

class CollectionGroupResourceExtension extends ResourceExtension
{
    public function extendTable(Table $table): Table
    {
        $columns = array_values(array_filter(
            $table->getColumns(),
            fn ($column) => $column->getName() !== 'handle'
        ));

        return $table->columns($columns);
    }

    public function extendForm(Form $form): Form
    {
        $schema = $form->getComponents(withHidden: true);
        $this->hideHandleField($schema);

        return $form->schema($schema);
    }

    protected function hideHandleField(array $components): void
    {
        foreach ($components as $component) {
            if ($component instanceof TextInput && $component->getName() === 'handle') {
                $component->hidden(true);
            }

            if (method_exists($component, 'getChildComponents')) {
                $this->hideHandleField($component->getChildComponents());
            }
        }
    }
}
