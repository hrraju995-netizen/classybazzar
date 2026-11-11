<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

    /*
     * The Next.js storefront origins allowed to call this API.
     * Set FRONTEND_URL (and optionally FRONTEND_URLS as a comma-separated
     * list) in .env — never leave this as '*' in production, because a
     * wildcard here would let any site read authenticated responses.
     */
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(
        ',',
        env('FRONTEND_URLS', env('FRONTEND_URL', 'http://localhost:3000'))
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
