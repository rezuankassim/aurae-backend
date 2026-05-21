<?php

namespace App\Lunar\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Lunar\Models\Contracts\Product as ProductContract;
use Lunar\Models\Product;
use Lunar\Shipping\Filament\Resources\ShippingExclusionListResource\RelationManagers\ShippingExclusionRelationManager as BaseShippingExclusionRelationManager;

class ShippingExclusionRelationManager extends BaseShippingExclusionRelationManager
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\MorphToSelect::make('purchasable')
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(Product::modelClass())
                            ->titleAttribute('name.en')
                            ->getOptionsUsing(static function (): array {
                                return Product::modelClass()::all()
                                    ->mapWithKeys(fn (ProductContract $record): array => [$record->getKey() => $record->attr('name')])
                                    ->all();
                            })
                            ->getSearchResultsUsing(static function (Forms\Components\Select $component, string $search): array {
                                return get_search_builder(Product::modelClass(), $search)
                                    ->get()
                                    ->mapWithKeys(fn (ProductContract $record): array => [$record->getKey() => $record->attr('name')])
                                    ->all();
                            }),
                    ])
                    ->label(
                        __('lunarpanel.shipping::relationmanagers.exclusions.form.purchasable.label')
                    )
                    ->required()
                    ->searchable(true)
                    ->preload(),
            ]);
    }
}
