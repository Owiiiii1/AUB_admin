<?php

return [

    /*
    | Native Flutter does not use browser CORS. Do not open wildcard origins
    | "so that it works". Flutter Web can be decided later.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
