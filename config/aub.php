<?php

return [

    'api' => [
        'version' => 'v1',
        'token_expiration_minutes' => (int) env('AUB_API_TOKEN_EXPIRATION_MINUTES', 43200) ?: 43200,
        'login_max_attempts_per_minute' => 5,
        'authenticated_max_attempts_per_minute' => 120,
        'token_ability' => 'mobile',
        'device_name_max_length' => 120,
    ],

];
