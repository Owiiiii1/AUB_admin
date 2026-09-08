<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Flutter native uses Bearer tokens, not SPA cookies. Leave empty unless
    | a first-party browser SPA is introduced later.
    |
    */

    'stateful' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', '')),
    ))),

    'guard' => ['web'],

    'expiration' => (int) env('AUB_API_TOKEN_EXPIRATION_MINUTES', 43200) ?: 43200,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
