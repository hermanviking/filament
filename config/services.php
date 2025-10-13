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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'visma' => [
        'sales_order_base_url' => env('VISMA_SALES_ORDER_BASE_URL', 'https://salesorder.visma.net/api/v3'),
        'sales_order_type' => env('VISMA_SALES_ORDER_TYPE', 'SO'),
        'default_currency' => env('VISMA_DEFAULT_CURRENCY', 'NOK'),
            'default_terms_id'      => env('VISMA_DEFAULT_TERMS_ID', 'NET30'),
    'default_location_id'   => env('VISMA_DEFAULT_LOCATION_ID', 'Main'),

        'tenant_id' => env('VISMA_TENANT_ID', env('VISMA_TENANT_ID_LIVE')),
            'legacy_base_url' => env('VISMA_LEGACY_BASE_URL', 'https://integration.visma.net/API/controller/api/v1'),

    ],

];
