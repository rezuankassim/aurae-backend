<?php

namespace App\Lunar\Extensions;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\RelationManagerExtension;
use Lunar\Models\Collection;
use Lunar\Models\Contracts\Collection as CollectionContract;
use Lunar\Models\Contracts\Product as ProductContract;
use Lunar\Models\Contracts\ProductVariant as ProductVariantContract;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

class ProductConditionRelationManagerExtension extends RelationManagerExtension
{
    public function extendTable(Table $table): Table
    {
        return $table->headerActions([
            Tables\Actions\CreateAction::make()->form([
                Forms\Components\MorphToSelect::make('discountable')
                    ->searchable(true)
                    ->preload()
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(Collection::modelClass())
                            ->titleAttribute('name.en')
                            ->getSearchResultsUsing(static function (Forms\Components\Select $component, string $search): array {
                                return get_search_builder(Collection::modelClass(), $search)
                                    ->get()
                                    ->mapWithKeys(fn (CollectionContract $record): array => [$record->getKey() => $record->attr('name')])
                                    ->all();
                            }),

                        Forms\Components\MorphToSelect\Type::make(Product::modelClass())
                            ->titleAttribute('name.en')
                            ->getSearchResultsUsing(static function (Forms\Components\Select $component, string $search): array {
                                return get_search_builder(Product::modelClass(), $search)
                                    ->get()
                                    ->mapWithKeys(fn (ProductContract $record): array => [$record->getKey() => $record->attr('name')])
                                    ->all();
                            }),

                        Forms\Components\MorphToSelect\Type::make(ProductVariant::modelClass())
                            ->titleAttribute('sku')
                            ->getSearchResultsUsing(static function (Forms\Components\Select $component, string $search): array {
                                return get_search_builder(ProductVariant::modelClass(), $search)
                                    ->orWhere('sku', 'like', $search.'%')
                                    ->get()
                                    ->mapWithKeys(fn (ProductVariantContract $record): array => [$record->getKey() => $record->product->attr('name').' - '.$record->sku])
                                    ->all();
                            }),
                    ]),
            ])->label(
                __('lunarpanel::discount.relationmanagers.conditions.actions.attach.label')
            )->mutateFormDataUsing(function (array $data) {
                $data['type'] = 'condition';

                return $data;
            }),
        ]);
    }
}
