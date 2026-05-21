<?php

namespace App\Lunar\Pages;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\BrandResource;
use Lunar\Admin\Filament\Resources\BrandResource\Pages\ManageBrandProducts as BaseManageBrandProducts;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Models\Contracts\Product as ProductContract;
use Lunar\Models\Product;

class ManageBrandProducts extends BaseManageBrandProducts
{
    protected static string $resource = BrandResource::class;

    public function table(Table $table): Table
    {
        return $table->columns([
            ProductResource::getNameTableColumn()->searchable()
                ->url(function (Model $record) {
                    return ProductResource::getUrl('edit', [
                        'record' => $record->getKey(),
                    ]);
                }),
            ProductResource::getSkuTableColumn(),
        ])->actions([
            DetachAction::make()
                ->action(function (Model $record) {
                    $record->update([
                        'brand_id' => null,
                    ]);

                    Notification::make()
                        ->success()
                        ->body(__('lunarpanel::brand.pages.products.actions.detach.notification.success'))
                        ->send();
                }),
        ])->headerActions([
            AttachAction::make()
                ->label(
                    __('lunarpanel::brand.pages.products.actions.attach.label')
                )
                ->form([
                    Forms\Components\Select::make('recordId')
                        ->label(
                            __('lunarpanel::brand.pages.products.actions.attach.form.record_id.label')
                        )
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(function (Forms\Components\Select $component, string $search): array {
                            return get_search_builder(Product::class, $search)
                                ->get()
                                ->mapWithKeys(fn (ProductContract $record): array => [$record->getKey() => $record->translateAttribute('name')])
                                ->all();
                        })
                        ->options(function (): array {
                            return Product::all()
                                ->mapWithKeys(fn (ProductContract $record): array => [$record->getKey() => $record->translateAttribute('name')])
                                ->all();
                        }),
                ])
                ->action(function (array $arguments, array $data) {
                    Product::where('id', '=', $data['recordId'])
                        ->update([
                            'brand_id' => $this->getRecord()->id,
                        ]);

                    Notification::make()
                        ->success()
                        ->body(__('lunarpanel::brand.pages.products.actions.attach.notification.success'))
                        ->send();
                }),
        ]);
    }
}
