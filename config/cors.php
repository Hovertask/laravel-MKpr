<?php

return [
    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'https://hovertask.com',
        'https://backend.hovertask.com',
        'https://app.hovertask.com',
        'http://app.hovertask.com',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];


// return [
//     'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'], // Define routes that should allow CORS
//     'allowed_methods' => ['*'],
//     'allowed_origins' => ['*'],  // You can specify a domain instead of '*'
//     'allowed_origins_patterns' => [],
//     'allowed_headers' => ['*'],
//     'exposed_headers' => [],
//     'max_age' => 0,
//     'supports_credentials' => true,

// ];