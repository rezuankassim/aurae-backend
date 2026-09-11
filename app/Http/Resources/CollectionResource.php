<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Lunar\Models\Currency;

class CollectionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $defaultCurrency = Currency::getDefault();

        return [
            'id' => $this->id,
            'name' => $this->translateAttribute('name'),
            'currency' => $defaultCurrency?->code,
            'payment_gateway_currency' => 'MYR',
            'products' => $this->whenLoaded('products', function () {
                return ProductResource::collection($this->products);
            }),
        ];
    }
}
