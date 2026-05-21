<?php

namespace App\Lunar\Search;

use Illuminate\Database\Eloquent\Model;
use Lunar\Search\ProductIndexer as BaseProductIndexer;

class ProductIndexer extends BaseProductIndexer
{
    public function toSearchableArray(Model $model): array
    {
        $data = array_merge([
            'id' => (string) $model->id,
            'status' => $model->status,
            'product_type' => $model->productType?->name,
            'brand' => $model->brand?->name,
            'created_at' => (int) $model->created_at->timestamp,
        ], $this->mapSearchableAttributes($model));

        if ($thumbnail = $model->thumbnail) {
            $data['thumbnail'] = $thumbnail->getUrl('small');
        }

        $data['skus'] = $model->variants->pluck('sku')->toArray();

        return $data;
    }
}
