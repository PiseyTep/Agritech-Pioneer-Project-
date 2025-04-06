<?php
return [
    'paths' => ['api/*'],
    'allowed_origins' => ['*'],  // In production, set specific origins
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
