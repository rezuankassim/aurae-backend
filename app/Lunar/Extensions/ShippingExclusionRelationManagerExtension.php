<?php

namespace App\Lunar\Extensions;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Support\Extending\RelationManagerExtension;
use Lunar\Models\Contracts\Product as ProductContract;
use Lunar\Models\Product;

class ShippingExclusionRelationManagerExtension extends RelationManagerExtension
{
    public function extendTable(Table $table): Table
    {
        return $table->headerActions([
            Tables\Actions\CreateAction::make()->form([
                Forms\Components\MorphToSelect::make('purchasable')
                    ->searchable(true)
                    ->preload()
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(Product::modelClass())
                            ->titleAttribute('name.en')
                            ->getOptionLabelUsing(
                                fn (Model $record) => $record->purchasable->attr('name')
                            )
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
                    ->required(),
            ]),
        ]);
    }
}
