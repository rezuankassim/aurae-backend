<?php

use Lunar\Base\OrderReferenceGenerator;

return [

    'reference_format' => [

        'prefix' => null,

        'padding_direction' => STR_PAD_LEFT,

        'padding_character' => '0',

        'length' => 8,
    ],

    'reference_generator' => OrderReferenceGenerator::class,

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
