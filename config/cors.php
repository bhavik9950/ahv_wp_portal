<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| The only route under `api/*` is the Meta webhook — a server-to-server call
| that never comes from a browser, so no cross-origin access is needed. Origins
| are locked to CORS_ALLOWED_ORIGINS (empty = none). If a browser-facing API is
| added later, list its exact origin(s) there — never "*".
|
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST'],

    'allowed_origins' => array_values(array_filter(
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
