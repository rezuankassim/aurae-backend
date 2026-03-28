<?php

namespace App\Lunar\Pages;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Admin\Events\ProductCollectionsUpdated;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductCollections as BaseManageProductCollections;
use Lunar\Admin\Support\Tables\Columns\TranslatedTextColumn;
use Lunar\Models\Contracts\Collection as CollectionContract;

class ManageProductCollections extends BaseManageProductCollections
{
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('position')
            ->columns([
                TranslatedTextColumn::make('attribute_data.name')
                    ->description(fn (CollectionContract $record): string => $record->breadcrumb->implode(' > '))
                    ->attributeData()
                    ->limitedTooltip()
                    ->limit(50)
                    ->label(__('lunarpanel::product.table.name.label')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordSelect(
                        function (Forms\Components\Select $select) {
                            return $select->placeholder(__('lunarpanel::product.pages.collections.select_collection'))
                                ->getSearchResultsUsing(static function (Forms\Components\Select $component, string $search, self $livewire): array {
                                    $relationModel = $livewire->getRelationship()->getRelated()::class;

                                    return get_search_builder($relationModel, $search)
                                        ->get()
                                        ->mapWithKeys(fn (CollectionContract $record): array => [$record->getKey() => $record->breadcrumb->push($record->translateAttribute('name'))->join(' > ')])
                                        ->all();
                                })
                                ->getOptionsUsing(static function (self $livewire): array {
                                    $relationModel = $livewire->getRelationship()->getRelated()::class;

                                    return $relationModel::all()
                                        ->mapWithKeys(fn (CollectionContract $record): array => [$record->getKey() => $record->breadcrumb->push($record->translateAttribute('name'))->join(' > ')])
                                        ->all();
                                });
                        }
                    )->after(
                        fn () => ProductCollectionsUpdated::dispatch(
                            $this->getOwnerRecord()
                        )
                    ),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()->after(
                    fn () => ProductCollectionsUpdated::dispatch(
                        $this->getOwnerRecord()
                    )
                ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->after(
                        fn () => ProductCollectionsUpdated::dispatch(
                            $this->getOwnerRecord()
                        )
                    ),
                ]),
            ]);
    }
}
