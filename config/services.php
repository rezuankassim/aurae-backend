<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'revpay' => [
        'merchant_id' => env('REVPAY_MERCHANT_ID'),
        'merchant_key' => env('REVPAY_MERCHANT_KEY'),
        'key_index' => env('REVPAY_KEY_INDEX', 1),
        'base_url' => env('REVPAY_BASE_URL', 'https://stg-mpg.revpay-sandbox.com.my/v1'),
        'currency' => env('REVPAY_CURRENCY', 'MYR'),
    ],

    'senangpay' => [
        'merchant_id' => env('SENANGPAY_MERCHANT_ID'),
        'secret_key' => env('SENANGPAY_SECRET_KEY'),
        'base_url' => env('SENANGPAY_BASE_URL', 'https://app.senangpay.my'),
        'recurring_base_url' => env('SENANGPAY_RECURRING_BASE_URL', 'https://api.senangpay.my'),
        'currency' => env('SENANGPAY_CURRENCY', 'MYR'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

];
