<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Itsme Client ID
    |--------------------------------------------------------------------------
    |
    | Your Itsme application client ID obtained from the Itsme developer portal.
    |
    */
    'client_id' => env('ITSME_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Itsme Client Secret
    |--------------------------------------------------------------------------
    |
    | Your Itsme application client secret obtained from the Itsme developer portal.
    |
    */
    'client_secret' => env('ITSME_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URI
    |--------------------------------------------------------------------------
    |
    | The URI where users will be redirected after authentication.
    | This must match one of the redirect URIs configured in your Itsme application.
    |
    */
    'redirect' => env('ITSME_REDIRECT_URI', '/itsme/callback'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Set to 'sandbox' for testing or 'production' for live environment.
    |
    */
    'environment' => env('ITSME_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | OpenID Connect Discovery URL
    |--------------------------------------------------------------------------
    |
    | The discovery endpoint URL for OpenID Connect configuration.
    | If not set, will use default based on environment.
    |
    */
    'discovery_url' => env('ITSME_DISCOVERY_URL'),

    /*
    |--------------------------------------------------------------------------
    | Authorization Endpoint
    |--------------------------------------------------------------------------
    |
    | The authorization endpoint URL. If not set, will be discovered automatically.
    |
    */
    'authorization_endpoint' => env('ITSME_AUTHORIZATION_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | Token Endpoint
    |--------------------------------------------------------------------------
    |
    | The token endpoint URL. If not set, will be discovered automatically.
    |
    */
    'token_endpoint' => env('ITSME_TOKEN_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | UserInfo Endpoint
    |--------------------------------------------------------------------------
    |
    | The UserInfo endpoint URL. If not set, will be discovered automatically.
    |
    */
    'userinfo_endpoint' => env('ITSME_USERINFO_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | Issuer
    |--------------------------------------------------------------------------
    |
    | The issuer identifier. Used for token validation.
    |
    */
    'issuer' => env('ITSME_ISSUER'),

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | The scopes to request during authentication.
    |
    */
    'scopes' => [
        'openid',
        'profile',
        'email',
        'phone',
    ],

    /*
    |--------------------------------------------------------------------------
    | Use PKCE
    |--------------------------------------------------------------------------
    |
    | Enable PKCE (Proof Key for Code Exchange) for enhanced security.
    | Recommended for all applications.
    |
    */
    'use_pkce' => env('ITSME_USE_PKCE', true),

    /*
    |--------------------------------------------------------------------------
    | Verify Token Signature
    |--------------------------------------------------------------------------
    |
    | Whether to verify the JWT token signature using JWKS.
    | Recommended to keep enabled in production.
    |
    */
    'verify_token_signature' => env('ITSME_VERIFY_TOKEN', true),

    /*
    |--------------------------------------------------------------------------
    | Environment Endpoints
    |--------------------------------------------------------------------------
    |
    | Default endpoints for sandbox and production environments.
    |
    */
    'environments' => [
        'sandbox' => [
            'discovery_url' => 'https://idp.sandbox.itsme.be/.well-known/openid-configuration',
            'issuer' => 'https://idp.sandbox.itsme.be',
        ],
        'production' => [
            'discovery_url' => 'https://idp.itsme.be/.well-known/openid-configuration',
            'issuer' => 'https://idp.itsme.be',
        ],
    ],
];

