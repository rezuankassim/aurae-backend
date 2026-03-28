<?php

namespace App\Lunar\Extensions;

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
}
