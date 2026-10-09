<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Glinche (partenaires) - V1
    |--------------------------------------------------------------------------
    | Tout est configurable via .env : si la documentation indique un chemin
    | ou un nom de champ différent, il suffit de modifier le .env.
    */

    'base_url' => env('GLINCHE_API_BASE_URL', 'https://marketplace-dev.glinche-automobiles.com'),

    // "token" : POST sur login_path puis Authorization: Bearer <token>
    // "basic" : HTTP Basic (identifiant / mot de passe sur chaque requête)
    'auth_mode' => env('GLINCHE_AUTH_MODE', 'token'),

    'login_path' => env('GLINCHE_LOGIN_PATH', '/api/v1/login'),
    'login_email_field' => env('GLINCHE_LOGIN_EMAIL_FIELD', 'email'),
    'login_password_field' => env('GLINCHE_LOGIN_PASSWORD_FIELD', 'password'),

    'vehicles_path' => env('GLINCHE_VEHICLES_PATH', '/api/v1/vehicles'),

    // Identifiants : uniquement dans .env (jamais dans le code ni dans Git)
    'email' => env('GLINCHE_EMAIL'),
    'password' => env('GLINCHE_PASSWORD'),

    'timeout' => (int) env('GLINCHE_TIMEOUT', 10),            // secondes
    'token_ttl' => (int) env('GLINCHE_TOKEN_TTL', 3000),       // secondes
    'vehicles_cache_ttl' => (int) env('GLINCHE_VEHICLES_CACHE_TTL', 300), // 0 = pas de cache
    'max_pages' => (int) env('GLINCHE_MAX_PAGES', 20),
];
