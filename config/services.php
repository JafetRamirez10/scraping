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

    'serpapi' => [
        'key' => env('SERPAPI_KEY'),
        'engine' => env('SERPAPI_ENGINE', 'google'),
        'timeout' => (int) env('SERPAPI_TIMEOUT', 30),
        'location' => env('SERPAPI_LOCATION', 'Mexico'),
        'gl' => env('SERPAPI_GL', 'mx'),
        'hl' => env('SERPAPI_HL', 'es'),
        'num_results' => (int) env('SERPAPI_NUM_RESULTS', 10),
    ],

    'brevo' => [
        'webhook_secret' => env('BREVO_WEBHOOK_SECRET'),
    ],

    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout' => (int) env('DEEPSEEK_TIMEOUT', 30),
        'temperature' => (float) env('DEEPSEEK_TEMPERATURE', 0.7),
        'daily_limit' => (int) env('DEEPSEEK_DAILY_LIMIT', 200),
    ],

];
