<?php

namespace App\Lunar\Extensions;

use App\Models\Discount;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

class DiscountResourceExtension extends ResourceExtension
{
    public function extendTable(Table $table): Table
    {
        foreach ($table->getColumns() as $column) {
            if ($column instanceof TextColumn && $column->getName() === 'status') {
                $column
                    ->formatStateUsing(function ($state) {
                        return __("lunarpanel::discount.table.status.{$state}.label");
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Discount::ACTIVE => 'success',
                        Discount::EXPIRED => 'danger',
                        Discount::PENDING => 'gray',
                        Discount::SCHEDULED => 'info',
                        Discount::DRAFT => 'warning',
                        default => 'gray',
                    });
            }
        }

        return $table;
    }
}
