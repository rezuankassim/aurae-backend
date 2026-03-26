<?php

namespace App\Lunar\Extensions;

use Filament\Actions;
use Filament\Forms;
use Lunar\Admin\Filament\Resources\DiscountResource;
use Lunar\Admin\Support\Extending\ListPageExtension;

class DiscountListExtension extends ListPageExtension
{
    public function headerActions(array $actions): array
    {
        return [
            Actions\CreateAction::make()->form([
                Forms\Components\Group::make([
                    DiscountResource::getNameFormComponent(),
                    DiscountResource::getHandleFormComponent(),
                ])->columns(2),
                Forms\Components\Group::make([
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label(__('lunarpanel::discount.form.starts_at.label'))
                        ->required()
                        ->minDate(now()->startOfDay())
                        ->before(function (Forms\Get $get) {
                            return $get('ends_at');
                        }),
                    DiscountResource::getEndsAtFormComponent(),
                ])->columns(2),
                DiscountResource::getDiscountTypeFormComponent(),
            ]),
        ];
    }
}
