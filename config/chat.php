<?php

return [
    'enabled' => env('CHAT_ENABLED', false),
    'signed_url_ttl' => env('CHAT_SIGNED_URL_TTL', 900),
    'redact_messages' => env('CHAT_REDACT_MESSAGES', true),
    'allowed_image_mime' => env('CHAT_ALLOWED_IMAGE_MIME', 'image/jpeg,image/png,image/webp'),
];