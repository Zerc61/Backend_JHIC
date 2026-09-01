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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | EJT AI Core (FastAPI microservice)
    |--------------------------------------------------------------------------
    | Laravel berperan sebagai gerbang (proxy) aman: frontend hanya bicara ke
    | Laravel (via Sanctum), lalu Laravel meneruskan ke FastAPI dengan shared
    | secret pada header `X-AI-Secret`.
    */
    'ai' => [
        'base_url' => env('AI_BASE_URL', 'http://127.0.0.1:8000'),
        'api_key' => env('AI_API_KEY', ''), // shared secret server-to-server
        'secret_header' => 'X-AI-Secret',
        'timeout' => (int) env('AI_TIMEOUT', 0), // 0 = tanpa timeout (stream)
        'chat_endpoint' => '/v1/chat/stream',
        'trip_plan_endpoint' => '/v1/trip/plan',
        'sync_enabled' => (bool) env('AI_SYNC_ENABLED', false),
    ],

];
