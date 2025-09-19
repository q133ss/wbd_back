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
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'pusher' => [
        'id'      => env('PUSHER_APP_ID'),
        'key'     => env('PUSHER_APP_KEY'),
        'secret'  => env('PUSHER_APP_SECRET'),
        'cluster' => env('PUSHER_APP_CLUSTER'),
    ],

    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'username' => env('TELEGRAM_BOT_USERNAME'),
        'client_token' => env('TELEGRAM_CLIENT_TOKEN'),
        'client_username' => env('TELEGRAM_CLIENT_USERNAME'),
        'autopost_token' => env('TELEGRAM_AUTOPOST_BOT_TOKEN'),
        'autopost_chat_id' => env('TELEGRAM_AUTOPOST_CHAT_ID', '-3026543670'),
    ],

    'cloudpayments' => [
        'base_uri'    => env('CLOUDPAYMENTS_BASE_URI', 'https://api.cloudpayments.ru'),
        'public_key'  => env('CLOUDPAYMENTS_PUBLIC_KEY'),
        'secret_key'  => env('CLOUDPAYMENTS_SECRET_KEY'),
    ]
];
