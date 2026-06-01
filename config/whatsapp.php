<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Access token (System User / permanent token)
    |--------------------------------------------------------------------------
    */
    'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Phone number id & business account id
    |--------------------------------------------------------------------------
    */
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | App id & secret (webhook signature + Embedded Signup onboarding)
    |--------------------------------------------------------------------------
    |
    | app_id is required only for multi-tenant onboarding (Embedded Signup).
    |
    */
    'app_id' => env('WHATSAPP_APP_ID'),
    'app_secret' => env('WHATSAPP_APP_SECRET'),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint & version (advanced)
    |--------------------------------------------------------------------------
    */
    'graph_url' => env('WHATSAPP_GRAPH_URL', 'https://graph.facebook.com'),
    'version' => env('WHATSAPP_API_VERSION', 'v23.0'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('WHATSAPP_TIMEOUT', 30),
];
