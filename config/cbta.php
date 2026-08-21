<?php

return [
    'base_url' => env('CBTA_BASE_URL', 'http://127.0.0.1:8080'),
    'token' => env('CBTA_API_TOKEN'),
    'timeout' => (int) env('CBTA_API_TIMEOUT', 10),
    'connect_timeout' => (int) env('CBTA_API_CONNECT_TIMEOUT', 3),
    'webhook_secret' => env('CBTA_WEBHOOK_SECRET'),
    'webhook_tolerance' => (int) env('CBTA_WEBHOOK_TOLERANCE', 300),
    'max_sync_attempts' => (int) env('CBTA_MAX_SYNC_ATTEMPTS', 10),
    'max_retry_delay_minutes' => (int) env('CBTA_MAX_RETRY_DELAY_MINUTES', 60),
];
