<?php

namespace App\Lunar\Extensions;

use Filament\Actions;
use Lunar\Admin\Support\Extending\EditPageExtension;

class CustomerGroupEditExtension extends EditPageExtension
{
    public function headerActions(array $actions): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    $record->customers()->detach();
                    $record->discounts()->detach();
                    $record->products()->detach();
                    $record->collections()->detach();
                }),
        ];
    }
}
