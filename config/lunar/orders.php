<?php

use Lunar\Base\OrderReferenceGenerator;

return [
    /*
    |--------------------------------------------------------------------------
    | Order Reference Format
    |--------------------------------------------------------------------------
    |
    | Specify the format for the order reference generator to use.
    |
    */
    'reference_format' => [
        /**
         * Optional prefix for the order reference
         */
        'prefix' => null,

        /**
         * STR_PAD_LEFT: 00001965
         * STR_PAD_RIGHT: 19650000
         * STR_PAD_BOTH: 00196500
         */
        'padding_direction' => STR_PAD_LEFT,

        /**
         * 00001965
         * AAAA1965
         */
        'padding_character' => '0',

        /**
         * If the length specified below is smaller than the length
         * of the Order ID, then no padding will take place.
         */
        'length' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Reference Generator
    |--------------------------------------------------------------------------
    |
    | Here you can specify how you want your order references to be generated
    | when you create an order from a cart.
    |
    */
    'reference_generator' => OrderReferenceGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Draft Status
    |--------------------------------------------------------------------------
    |
    | When a draft order is created from a cart, we need an initial status for
    | the order that's created. Define that here, it can be anything that would
    | make sense for the store you're building.
    |
    */
    'draft_status' => 'payment-pending',

    'statuses' => [

        'payment-pending' => [
            'label' => 'Payment Pending',
            'color' => '#848a8c',
            'mailers' => [],
            'notifications' => [],
            'favourite' => true,
        ],

        'payment-received' => [
            'label' => 'Payment Received',
            'color' => '#6a67ce',
            'mailers' => [
                \App\Mail\Orders\OrderStatusUpdatedMail::class,
            ],
            'notifications' => [],
            'favourite' => true,
        ],

        'payment-failed' => [
            'label' => 'Payment Failed',
            'color' => '#e74c3c',
            'mailers' => [
                \App\Mail\Orders\OrderStatusUpdatedMail::class,
            ],
            'notifications' => [],
            'favourite' => true,
        ],

        'dispatched' => [
            'label' => 'Dispatched',
            'mailers' => [
                \App\Mail\Orders\OrderStatusUpdatedMail::class,
            ],
            'notifications' => [],
            'favourite' => true,
        ],

        'delivered' => [
            'label' => 'Delivered',
            'color' => '#2ecc71',
            'mailers' => [
                \App\Mail\Orders\OrderStatusUpdatedMail::class,
            ],
            'notifications' => [],
            'favourite' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Pipelines
    |--------------------------------------------------------------------------
    |
    | Define which pipelines should be run throughout an order's lifecycle.
    | The default ones provided should suit most needs, however you are
    | free to add your own as you see fit.
    |
    | Each pipeline class will be run from top to bottom.
    |
    */
    'pipelines' => [
        'creation' => [
            App\Pipelines\Order\Creation\FillOrderFromCart::class,
            App\Pipelines\Order\Creation\CreateOrderLines::class,
            Lunar\Pipelines\Order\Creation\CreateOrderAddresses::class,
            Lunar\Pipelines\Order\Creation\CreateShippingLine::class,
            App\Pipelines\Order\Creation\CleanUpOrderLines::class,
            Lunar\Pipelines\Order\Creation\MapDiscountBreakdown::class,
        ],
    ],

];
