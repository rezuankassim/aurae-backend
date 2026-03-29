<?php

namespace App\Lunar\Extensions;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Grid;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Support\Extending\ListPageExtension;
use Lunar\Models\ProductType;

class ListProductsExtension extends ListPageExtension
{
    public function headerActions(array $actions): array
    {
        return collect($actions)->map(function ($action) {
            if ($action instanceof CreateAction) {
                $action->form([
                    Grid::make(2)->schema([
                        ProductResource::getBaseNameFormComponent(),
                    ]),
                    Grid::make(2)->schema([
                        ProductResource::getSkuFormComponent(),
                        ProductResource::getBasePriceFormComponent(),
                    ]),
                ])->mutateFormDataUsing(function (array $data): array {
                    $data['product_type_id'] = ProductType::first()->id;

                    return $data;
                });
            }

            return $action;
        })->all();
    }
}
