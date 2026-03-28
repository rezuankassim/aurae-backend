<?php

namespace App\Lunar\Extensions;

use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

class CustomerResourceExtension extends ResourceExtension
{
    public function extendTable(Table $table): Table
    {
        return $table->bulkActions([]);
    }
}
