<?php

return [

    'environment' => env('SAGE_ENVIRONMENT', 'sandbox'),

    'sandbox' => [
        'api_base' => 'https://api-dev.network.sage.com',
        'auth_url' => 'https://login.eu.dev.lockstep.io/login.eu.dev.lockstep.io/B2C_1A_PLATFORMSIGNUP_SIGNIN/oauth2/v2.0/authorize',
        'token_url' => 'https://login.eu.dev.lockstep.io/login.eu.dev.lockstep.io/B2C_1A_PLATFORMSIGNUP_SIGNIN/oauth2/v2.0/token',
        'scope' => 'openid https://login.eu.dev.lockstep.io/platform-api/api.call',
    ],

    'production' => [
        'api_base' => 'https://api.network.sage.com',
        'auth_url' => 'https://login.eu.lockstep.io/login.eu.lockstep.io/B2C_1A_PLATFORMSIGNUP_SIGNIN/oauth2/v2.0/authorize',
        'token_url' => 'https://login.eu.lockstep.io/login.eu.lockstep.io/B2C_1A_PLATFORMSIGNUP_SIGNIN/oauth2/v2.0/token',
        'scope' => 'openid https://login.eu.lockstep.io/platform-api/api.call',
    ],

];
