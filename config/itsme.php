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
    | Cannot be disabled outside the local/testing environments.
    |
    */
    'verify_token_signature' => env('ITSME_VERIFY_TOKEN', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed Signature Algorithms
    |--------------------------------------------------------------------------
    |
    | Whitelist of JWT signature algorithms accepted when verifying ID tokens.
    | Never derived from the token header (prevents algorithm confusion).
    |
    */
    'allowed_algorithms' => ['RS256'],

    /*
    |--------------------------------------------------------------------------
    | Clock Tolerance
    |--------------------------------------------------------------------------
    |
    | Leeway in seconds tolerated when checking exp/iat/nbf claims.
    |
    */
    'clock_tolerance' => (int) env('ITSME_CLOCK_TOLERANCE', 60),

    /*
    |--------------------------------------------------------------------------
    | JWKS URI
    |--------------------------------------------------------------------------
    |
    | Optional explicit JWKS URL. If unset, it is resolved from the OpenID
    | discovery document of the current environment.
    |
    */
    'jwks_uri' => env('ITSME_JWKS_URI'),

    /*
    |--------------------------------------------------------------------------
    | JWKS Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long (seconds) the JWKS response is cached to avoid a network call
    | on every token validation.
    |
    */
    'jwks_cache_ttl' => (int) env('ITSME_JWKS_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Link Accounts By Email
    |--------------------------------------------------------------------------
    |
    | When a login has no matching itsme_id, optionally match an existing
    | account by verified email. Only accounts whose email_verified_at is set
    | are eligible, and the itsme email must itself be verified.
    | Disabled by default (strict itsme_id linking).
    |
    */
    'link_by_email' => (bool) env('ITSME_LINK_BY_EMAIL', false),

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

