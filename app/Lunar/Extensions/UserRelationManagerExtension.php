<?php

namespace App\Lunar\Extensions;

use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\RelationManagerExtension;

class UserRelationManagerExtension extends RelationManagerExtension
{
    public function extendTable(Table $table): Table
    {
        return $table->actions([]);
    }
}
