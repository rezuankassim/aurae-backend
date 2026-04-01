<?php

namespace App\Lunar\Pages;

use Awcodes\Shout\Components\Shout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lunar\Shipping\Filament\Resources\ShippingZoneResource\Pages\ManageShippingRates as BaseManageShippingRates;
use Lunar\Shipping\Models\ShippingRate;

class ManageShippingRatesPage extends BaseManageShippingRates
{
    public function form(Form $form): Form
    {
        return $form->schema([
            Shout::make('')->content(
                function () {
                    $pricesIncTax = config('lunar.pricing.stored_inclusive_of_tax', false);

                    if ($pricesIncTax) {
                        return __('lunarpanel.shipping::relationmanagers.shipping_rates.notices.prices_inc_tax');
                    }

                    return __('lunarpanel.shipping::relationmanagers.shipping_rates.notices.prices_excl_tax');
                }
            ),
            Forms\Components\Select::make('shipping_method_id')
                ->label(
                    __('lunarpanel.shipping::relationmanagers.shipping_rates.form.shipping_method_id.label')
                )
                ->required()
                ->live()
                ->relationship(name: 'shippingMethod', titleAttribute: 'name')
                ->columnSpan(2),
            Forms\Components\TextInput::make('price')
                ->label(
                    __('lunarpanel.shipping::relationmanagers.shipping_rates.form.price.label')
                )
                ->numeric()
                ->required()
                ->columnSpan(2)
                ->afterStateHydrated(static function (Forms\Components\TextInput $component, ?Model $record = null): void {
                    if ($record) {
                        $basePrice = $record->basePrices->first();

                        $component->state(
                            $basePrice->price->decimal
                        );
                    }
                }),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('shippingMethod.name')
                ->label(
                    __('lunarpanel.shipping::relationmanagers.shipping_rates.table.shipping_method.label')
                ),
            TextColumn::make('basePrices.0')->formatStateUsing(
                fn ($state = null) => $state->price->formatted
            )->label(
                __('lunarpanel.shipping::relationmanagers.shipping_rates.table.price.label')
            ),
        ])->headerActions([
            Tables\Actions\CreateAction::make()->label(
                __('lunarpanel.shipping::relationmanagers.shipping_rates.actions.create.label')
            )->action(function (Table $table, ?ShippingRate $shippingRate = null, array $data = []) {
                $relationship = $table->getRelationship();

                $record = new ShippingRate;
                $record->shipping_method_id = $data['shipping_method_id'];
                $relationship->save($record);

                static::saveShippingRate($record, $data);
            })->slideOver()->createAnother(false),
        ])->actions([
            Tables\Actions\EditAction::make()->slideOver()->action(function (ShippingRate $shippingRate, array $data) {
                static::saveShippingRate($shippingRate, $data);
            }),
            Tables\Actions\DeleteAction::make()->requiresConfirmation(),
        ]);
    }
}
