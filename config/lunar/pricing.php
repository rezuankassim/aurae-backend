<?php

use Lunar\Pricing\DefaultPriceFormatter;

return [

    'stored_inclusive_of_tax' => env('LUNAR_STORE_INCLUSIVE_OF_TAX', false),

    'formatter' => DefaultPriceFormatter::class,

    'pipelines' => [

    ],

];
