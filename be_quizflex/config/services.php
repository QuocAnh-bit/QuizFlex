<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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

    'momo' => [
        'partner_code' => env('MOMO_PARTNER_CODE', 'MOMOBKUN20180810'),
        'access_key' => env('MOMO_ACCESS_KEY', 'klm05Y2lhgWgl2uU'),
        'secret_key' => env('MOMO_SECRET_KEY', 'escribe551051'),
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'redirect_url' => env('MOMO_REDIRECT_URL', 'http://localhost:5173/payment-result'),
        'ipn_url' => env('MOMO_IPN_URL', 'http://localhost:8000/api/payments/webhook/momo'),
    ],

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_uri' => env('DEEPSEEK_BASE_URI', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout' => (int) env('DEEPSEEK_TIMEOUT', 120),
        'connect_timeout' => (int) env('DEEPSEEK_CONNECT_TIMEOUT', 30),
        'ssl_verify' => filter_var(env('DEEPSEEK_SSL_VERIFY', true), FILTER_VALIDATE_BOOL),
        'ca_bundle' => env('DEEPSEEK_CA_BUNDLE'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_uri' => env('OPENROUTER_BASE_URI', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'deepseek/deepseek-chat-v3-0324'),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 120),
        'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 30),
        'http_referer' => env('OPENROUTER_HTTP_REFERER', env('APP_URL', 'http://localhost:8000')),
        'title' => env('OPENROUTER_TITLE', env('APP_NAME', 'QuizFlex')),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

];