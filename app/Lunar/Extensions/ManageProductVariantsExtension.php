<?php

namespace App\Lunar\Extensions;

use App\Lunar\Widgets\ProductOptionsWidget;
use Lunar\Admin\Support\Extending\RelationPageExtension;

class ManageProductVariantsExtension extends RelationPageExtension
{
    public function headerWidgets(array $widgets): array
    {
        return array_map(
            fn ($widget) => $widget === \Lunar\Admin\Filament\Resources\ProductResource\Widgets\ProductOptionsWidget::class
                ? ProductOptionsWidget::class
                : $widget,
            $widgets,
        );
    }
}
