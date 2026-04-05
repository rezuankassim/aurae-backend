<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Lunar\Facades\Pricing;

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
            'description' => $this->cleanDescription($this->translateAttribute('description')),
            'thumbnail' => $this->thumbnail ? [
                'url' => $this->thumbnail->getUrl(),
                'name' => $this->translateAttribute('name'),
            ] : null,
            'price' => $this->getPricing()?->price->formatted(),
            'main_image' => $this->getMainImage(),
            'images' => $this->getImages(),
            'variants' => $this->whenLoaded('variants', function () {
                $sorted = $this->variants->sortBy(function ($variant) {
                    return $variant->values
                        ->sortBy(fn ($v) => optional($v->option)->position ?? 0)
                        ->map(fn ($v) => str_pad((string) ($v->position ?? 0), 5, '0', STR_PAD_LEFT))
                        ->implode('-');
                });

                return ProductVariantResource::collection($sorted->values());
            }),
            'options' => $this->getProductOptions(),
        ];
    }

    /**
     * Strip Trix attachment captions (filename/size) from HTML description.
     */
    protected function cleanDescription(?string $html): ?string
    {
        if (! $html) {
            return $html;
        }

        // Remove <figcaption class="attachment__caption">...</figcaption>
        return preg_replace('/<figcaption[^>]*class="[^"]*attachment__caption[^"]*"[^>]*>.*?<\/figcaption>/s', '', $html);
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
