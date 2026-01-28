<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Apple Wallet Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Apple Wallet (PassKit) pass generation.
    | Requires Apple Developer account and certificates.
    |
    */
    'apple' => [
        'enabled' => env('WALLET_APPLE_ENABLED', false),
        'pass_type_id' => env('WALLET_APPLE_PASS_TYPE_ID', 'pass.com.nrapa.membership'),
        'team_id' => env('WALLET_APPLE_TEAM_ID', ''),
        'certificate_path' => env('WALLET_APPLE_CERTIFICATE_PATH', storage_path('wallet/apple/certificate.pem')),
        'wwdr_certificate_path' => env('WALLET_APPLE_WWDR_PATH', storage_path('wallet/apple/wwdr.pem')),
        'certificate_password' => env('WALLET_APPLE_CERTIFICATE_PASSWORD', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Wallet Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Wallet pass generation.
    | Requires Google Wallet API issuer account.
    |
    */
    'google' => [
        'enabled' => env('WALLET_GOOGLE_ENABLED', false),
        'issuer_id' => env('WALLET_GOOGLE_ISSUER_ID', ''),
        'service_account_path' => env('WALLET_GOOGLE_SERVICE_ACCOUNT_PATH', storage_path('wallet/google/service-account.json')),
        'application_name' => env('WALLET_GOOGLE_APPLICATION_NAME', 'NRAPA Membership'),
    ],
];
