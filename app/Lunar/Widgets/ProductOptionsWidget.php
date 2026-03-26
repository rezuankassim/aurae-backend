<?php

namespace App\Lunar\Widgets;

use Lunar\Admin\Filament\Resources\ProductResource\Widgets\ProductOptionsWidget as BaseProductOptionsWidget;
use Lunar\Models\ProductOption;

class ProductOptionsWidget extends BaseProductOptionsWidget
{
    public function addSharedOptionAction()
    {
        $existing = collect($this->configuredOptions)->pluck('id');
        $hasSharedOptions = ProductOption::whereNotIn('id', $existing)
            ->shared()
            ->exists();

        return parent::addSharedOptionAction()
            ->modalSubmitAction(fn ($action) => $hasSharedOptions ? $action : $action->disabled());
    }
}
