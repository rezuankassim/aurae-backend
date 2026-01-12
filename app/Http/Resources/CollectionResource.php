<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Lunar\Models\Currency;

class CollectionResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $defaultCurrency = Currency::getDefault();

        return [
            'id' => $this->id,
            'name' => $this->translateAttribute('name'),
            'currency' => $defaultCurrency?->code,
            'payment_gateway_currency' => 'MYR', // RevPay requirement (ISO 4217)
            'products' => $this->whenLoaded('products', function () {
                return ProductResource::collection($this->products);
            }),
        ];
    }
}
