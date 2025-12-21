<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CollectionResource extends BaseResource
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
            'name' => $this->translateAttribute('name'),
            'products' => $this->whenLoaded('products', function () {
                return ProductResource::collection($this->products);
            }),
        ];
    }
}
