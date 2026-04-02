<?php

namespace App\Lunar\Pages;

use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductVariantResource\Pages\ManageVariantPricing;
use Lunar\Models\Price;

class ManageVariantPricingPage extends ManageVariantPricing
{
    public function getRelationManagers(): array
    {
        return [];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data = $this->callLunarHook('beforeUpdate', $data, $record);

        $variant = $this->getOwnerRecord();

        // Merge form-returned prices with the full basePrices data
        // because getState() only returns validated component fields (value, compare_price)
        // but the handler needs id, factor, currency_id, original_value, etc.
        $formPrices = $data['basePrices'] ?? [];
        $prices = collect($this->basePrices)->map(function ($price, $index) use ($formPrices) {
            if (isset($formPrices[$index])) {
                return array_merge($price, $formPrices[$index]);
            }

            return $price;
        });

        unset($data['basePrices']);
        $variant->update($data);

        $prices->filter(
            fn ($price) => ! $price['id'] && isset($price['value'])
        )->each(fn ($price) => $variant->prices()->create([
            'currency_id' => $price['currency_id'],
            'price' => (int) round((float) ($price['value'] * $price['factor'])),
            'compare_price' => (int) round((float) (($price['compare_price'] ?? 0) * $price['factor'])),
            'min_quantity' => 1,
            'customer_group_id' => null,
        ]));

        $prices->filter(
            fn ($price) => $price['id'] && isset($price['value']) && ($price['value'] != $price['original_value'] || ($price['compare_price'] ?? 0) != ($price['original_compare_price'] ?? 0))
        )->each(fn ($price) => Price::find($price['id'])->update([
            'price' => (int) round((float) ($price['value'] * $price['factor'])),
            'compare_price' => (int) round((float) (($price['compare_price'] ?? 0) * $price['factor'])),
        ]));

        $this->basePrices = $this->getBasePrices();

        $this->dispatch('refresh-relation-manager');

        return $this->callLunarHook('afterUpdate', $record, $data);
    }
}
