<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Lunar\Facades\Pricing;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price' => $this->getPricing()?->price->formatted(),
            'description' => $this->getDescription(),
            'values' => $this->values->map(function ($value) {
                return [
                    'id' => $value->id,
                    'product_option_id' => $value->product_option_id,
                    'name' => $value->translate('name'),
                    'position' => $value->position,
                    'option' => [
                        'id' => $value->option->id,
                        'name' => $value->option->translate('name'),
                    ]
                ];
            }),
            'values_ids' => $this->values->pluck('id'),
        ];
    }

    public function getPricing()
    {
        return Pricing::for($this->resource)->get()->matched;
    }
}
