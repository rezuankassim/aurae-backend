<?php

namespace App\Lunar\Extensions;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\RelationManagerExtension;
use Lunar\Models\Contracts\Product as ProductContract;
use Lunar\Models\Product;

class ProductLimitationRelationManagerExtension extends RelationManagerExtension
{
    public function extendTable(Table $table): Table
    {
        return $table->headerActions([
            Tables\Actions\CreateAction::make()->form([
                Forms\Components\MorphToSelect::make('discountable')
                    ->searchable(true)
                    ->preload()
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(Product::modelClass())
                            ->titleAttribute('name.en')
                            ->getSearchResultsUsing(static function (Forms\Components\Select $component, string $search): array {
                                return get_search_builder(Product::modelClass(), $search)
                                    ->get()
                                    ->mapWithKeys(fn (ProductContract $record): array => [$record->getKey() => $record->attr('name')])
                                    ->all();
                            }),
                    ]),
            ])->label(
                __('lunarpanel::discount.relationmanagers.products.actions.attach.label')
            )->mutateFormDataUsing(function (array $data) {
                $data['type'] = 'limitation';

                return $data;
            }),
        ]);
    }
}
