<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'purchaseable' => $this->whenLoaded('purchasable', function () {
                return ProductVariantResource::make($this->purchasable);
            }),
        ];
    }
}
