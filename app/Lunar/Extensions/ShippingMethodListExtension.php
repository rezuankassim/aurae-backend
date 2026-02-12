<?php

namespace App\Lunar\Extensions;

use Filament\Actions;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Lunar\Admin\Support\Extending\ListPageExtension;
use Lunar\Models\CustomerGroup;
use Lunar\Shipping\Models\ShippingMethod;

class ShippingMethodListExtension extends ListPageExtension
{
    public function headerActions(array $actions): array
    {
        return [
            Actions\CreateAction::make()->form([
                TextInput::make('name')
                    ->label(__('lunarpanel.shipping::shippingmethod.form.name.label'))
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                Group::make([
                    TextInput::make('code')
                        ->label(__('lunarpanel.shipping::shippingmethod.form.code.label'))
                        ->required()
                        ->unique(ignoreRecord: true),
                    Select::make('driver')
                        ->label(__('lunarpanel.shipping::shippingmethod.form.driver.label'))
                        ->options([
                            'ship-by' => __('lunarpanel.shipping::shippingmethod.form.driver.options.ship-by'),
                            'collection' => __('lunarpanel.shipping::shippingmethod.form.driver.options.collection'),
                            'flat-rate' => __('lunarpanel.shipping::shippingmethod.form.driver.options.flat-rate'),
                            'free-shipping' => __('lunarpanel.shipping::shippingmethod.form.driver.options.free-shipping'),
                        ])
                        ->default('ship-by'),
                ])->columns(2),
                RichEditor::make('description')
                    ->label(__('lunarpanel.shipping::shippingmethod.form.description.label')),
            ])->after(function (ShippingMethod $shippingMethod) {
                $customerGroups = CustomerGroup::pluck('id')->mapWithKeys(
                    fn ($id) => [$id => ['visible' => true, 'enabled' => true, 'starts_at' => now()]]
                );
                $shippingMethod->customerGroups()->sync($customerGroups);
            }),
        ];
    }
}
