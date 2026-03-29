<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Lunar\Facades\Pricing;

class CartLineResource extends BaseResource
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
            'cart_id' => $this->cart_id,
            'purchaseable_id' => $this->purchasable_id,
            'purchaseable_type' => $this->purchasable_type,
            'subTotal' => $this->subTotal,
            'total' => $this->total,
            'quantity' => $this->quantity,
            'selected' => (bool) $this->selected,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'purchaseable' => $this->whenLoaded('purchasable', function () {
                $data = ProductVariantResource::make($this->purchasable)->toArray(request());

                if ($this->purchasable->relationLoaded('product') && $this->purchasable->product?->relationLoaded('variants')) {
                    $data['variants'] = $this->purchasable->product->variants->map(fn ($variant) => [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'thumbnail' => [
                            'url' => $variant->getThumbnail()?->getUrl(),
                        ],
                        'price' => Pricing::for($variant)->get()->matched?->price->formatted(),
                        'description' => $variant->getDescription(),
                        'values' => $variant->values->map(fn ($value) => [
                            'id' => $value->id,
                            'product_option_id' => $value->product_option_id,
                            'name' => $value->translate('name'),
                            'position' => $value->position,
                            'option' => [
                                'id' => $value->option->id,
                                'name' => $value->option->translate('name'),
                            ],
                        ]),
                        'values_ids' => $variant->values->pluck('id'),
                    ]);
                }

                return $data;
            }),
            'product' => $this->whenLoaded('purchasable', function () {
                if ($this->purchasable->relationLoaded('product')) {
                    return ProductResource::make($this->purchasable->product);
                }

                return null;
            }),
        ];
    }
}
