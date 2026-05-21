<?php

namespace App\Lunar\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Lunar\Admin\Events\CollectionProductAttached;
use Lunar\Admin\Events\CollectionProductDetached;
use Lunar\Admin\Filament\Resources\CollectionResource\Pages\ManageCollectionProducts as BaseManageCollectionProducts;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Models\Product;

class ManageCollectionProducts extends BaseManageCollectionProducts
{
    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\SpatieMediaLibraryImageColumn::make('thumbnail')
                ->collection(config('lunar.media.collection'))
                ->conversion('small')
                ->limit(1)
                ->square()
                ->label(''),
            Tables\Columns\TextColumn::make('attribute_data.name')
                ->formatStateUsing(fn (Model $record): string => $record->translateAttribute('name'))
                ->label(__('lunarpanel::product.table.name.label')),
        ])->actions([
            Tables\Actions\DetachAction::make()->after(
                fn () => CollectionProductDetached::dispatch($this->getOwnerRecord())
            ),
            Tables\Actions\EditAction::make()->url(
                fn (Model $record) => ProductResource::getUrl('edit', [
                    'record' => $record,
                ])
            ),
        ])->headerActions([
            Tables\Actions\AttachAction::make()
                ->label(
                    __('lunarpanel::collection.pages.products.actions.attach.label')
                )->form([
                    Forms\Components\Select::make('recordId')
                        ->label('Product')
                        ->required()
                        ->searchable()
                        ->options(function (self $livewire): array {
                            $existingIds = $livewire->getRelationship()->pluck('id')->toArray();

                            return Product::whereNotIn('id', $existingIds)
                                ->get()
                                ->mapWithKeys(fn (Product $product): array => [$product->getKey() => $product->translateAttribute('name')])
                                ->all();
                        }),
                ])->action(function (array $arguments, array $data, Form $form, Table $table) {
                    $relationship = Relation::noConstraints(fn () => $table->getRelationship());

                    $product = Product::find($data['recordId']);

                    $relationship->attach($product, [
                        'position' => $relationship->count() + 1,
                    ]);

                    CollectionProductAttached::dispatch($this->getOwnerRecord());

                    $product->searchable();
                }),
        ])->reorderable('position');
    }
}
