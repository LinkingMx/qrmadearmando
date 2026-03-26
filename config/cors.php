<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', env('APP_URL', 'http://localhost'))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'X-XSRF-TOKEN'],
    'exposed_headers' => ['ETag'],
    'max_age' => 0,
    'supports_credentials' => true,
];
