<?php

return [

    'models' => [

        Lunar\Models\Brand::class,
        Lunar\Models\Collection::class,
        Lunar\Models\Customer::class,
        Lunar\Models\Order::class,
        Lunar\Models\Product::class,
        Lunar\Models\ProductOption::class,

    ],

    'engine_map' => [

    ],

    'indexers' => [
        Lunar\Models\Brand::class => Lunar\Search\BrandIndexer::class,
        Lunar\Models\Collection::class => Lunar\Search\CollectionIndexer::class,
        Lunar\Models\Customer::class => Lunar\Search\CustomerIndexer::class,
        Lunar\Models\Order::class => Lunar\Search\OrderIndexer::class,
        Lunar\Models\Product::class => App\Lunar\Search\ProductIndexer::class,
        Lunar\Models\ProductOption::class => Lunar\Search\ProductOptionIndexer::class,
    ],

];
