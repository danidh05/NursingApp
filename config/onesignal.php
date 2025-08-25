<?php

return [
    'app_id' => env('ONESIGNAL_APP_ID'),
    'api_key' => env('ONESIGNAL_REST_API_KEY'), // REST API key
    'api_url' => 'https://api.onesignal.com/notifications',
    'user_auth_key' => env('ONESIGNAL_USER_AUTH_KEY', ''), // optional but needed
    'guzzle_client_timeout' => env('ONESIGNAL_TIMEOUT', 10), // optional
    'rest_api_url' => env('ONESIGNAL_API_URL', 'https://api.onesignal.com'),
];