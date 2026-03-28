<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Lunar\Models\ProductVariant as LunarProductVariant;

class ProductVariant extends LunarProductVariant
{
    /**
     * {@inheritDoc}
     */
    public function getOptions(): Collection
    {
        return $this->values->map(fn ($value) => $value->option?->translate('name').': '.$value->translate('name'));
    }
}
