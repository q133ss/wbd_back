<?php

return [
    'seller_group_slug' => env('TELEGRAM_SELLER_GROUP_SLUG'),
    'seller_group_url' => env('TELEGRAM_SELLER_GROUP_URL'),
    'seller_group_fetch_endpoint' => env('TELEGRAM_SELLER_GROUP_FETCH_ENDPOINT', 'https://t.me/s/%s'),
    'seller_export_disk' => env('TELEGRAM_SELLER_EXPORT_DISK', 'local'),
    'seller_export_path' => env('TELEGRAM_SELLER_EXPORT_PATH', 'telegram/sellers.csv'),
    'request_timeout' => env('TELEGRAM_SELLER_REQUEST_TIMEOUT', 10),
    'request_retries' => env('TELEGRAM_SELLER_REQUEST_RETRIES', 2),
];
