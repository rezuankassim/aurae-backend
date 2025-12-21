<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Lunar\Facades\Pricing;
use Lunar\Models\Product;

class ProductResource extends BaseResource
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
            'description' => $this->translateAttribute('description'),
            'thumbnail' => [
                'url' => $this->thumbnail->getUrl(),
                'name' => $this->translateAttribute('name'),
            ],
            'price' => $this->getPricing()?->price->formatted(),
            'main_image' => $this->getMainImage(),
            'images' => $this->getImages(),
            'variants' => $this->whenLoaded('variants', function () {
                return ProductVariantResource::collection($this->variants);
            }),
            'options' => $this->getProductOptions(),
        ];
    }

    public function getProductOptions()
    {
        return $this->variants->pluck('values')
            ->flatten()
            ->unique('id')
            ->groupBy('product_option_id')
            ->map(function ($values) {
                return [
                    'option' => [
                        'id' => $values->first()->option->id,
                        'name' => $values->first()->option->translate('name'),
                    ],
                    'values' => $values->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'name' => $value->translate('name'),
                        ];
                    })->values(),
                ];
            })->values();
    }

    public function getPricing()
    {
        return Pricing::for($this->resource->variants->first())->get()->matched;
    }

    public function getMainImage()
    {
        if (count($this->variant->images)) {
            $image = $this->variant->images->first();
        }

        if ($primary = $this->images->first(fn ($media) => $media->getCustomProperty('primary'))) {
            $image = $primary;
        }

        $image = $this->images->first();

        return [
            'url' => $image?->getUrl(),
            'name' => $this->translateAttribute('name'),
        ];
    }

    public function getImages()
    {
        $images = $this->media->sortBy('order_column');

        return $images->map(function ($image) {
            return [
                'url' => $image->getUrl(),
                'name' => $this->translateAttribute('name'),
            ];
        })->values();
    }
}
