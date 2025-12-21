<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartResource extends BaseResource
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
            'subtotal' => $this->subTotal,
            'shipping_total' => $this->shippingTotal,
            'total' => $this->total,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'lines' => CartLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
