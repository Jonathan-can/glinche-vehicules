<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Glinche (partenaires)
    |--------------------------------------------------------------------------
    | Authentification : POST sur login_path, puis Authorization: Bearer <token>.
    | Tout est configurable via .env.
    */

    'base_url' => env('GLINCHE_API_BASE_URL', 'https://marketplace-dev.glinche-automobiles.com'),

    'login_path' => env('GLINCHE_LOGIN_PATH', '/api/partners/login'),
    'vehicles_path' => env('GLINCHE_VEHICLES_PATH', '/api/partners/vehicles'),

    // Identifiants : uniquement dans .env (jamais dans le code ni dans Git)
    'email' => env('GLINCHE_EMAIL'),
    'password' => env('GLINCHE_PASSWORD'),

    'timeout' => (int) env('GLINCHE_TIMEOUT', 10),            // secondes
    'token_ttl' => (int) env('GLINCHE_TOKEN_TTL', 3000),       // secondes
    'vehicles_cache_ttl' => (int) env('GLINCHE_VEHICLES_CACHE_TTL', 300), // 0 = pas de cache
];
