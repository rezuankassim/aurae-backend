<?php

namespace App\Lunar\Extensions;

use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

class CustomerGroupResourceExtension extends ResourceExtension
{
    public function extendTable(Table $table): Table
    {
        return $table->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records) {
                        foreach ($records as $record) {
                            $record->customers()->detach();
                        }
                    }),
            ]),
        ]);
    }
}
