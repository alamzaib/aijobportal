<?php

$allowedOrigins = env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:3001');
$origins = $allowedOrigins ? explode(',', $allowedOrigins) : ['http://localhost:3000'];
// Trim whitespace from each origin
$origins = array_map('trim', $origins);

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];

