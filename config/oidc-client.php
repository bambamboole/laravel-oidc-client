<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Relying party
    |--------------------------------------------------------------------------
    |
    | This package turns a Laravel app into an OpenID Connect relying party: it
    | drives the Authorization-Code + PKCE flow against any OIDC provider,
    | validates the returned id_token against the provider's JWKS, and logs the
    | resolved user into the configured guard. For self-SSO, point `issuer` at
    | your own laravel-oidc provider.
    |
    */

    'enabled' => env('OIDC_RP_ENABLED', false),

    'issuer' => env('OIDC_RP_ISSUER'),

    'client_id' => env('OIDC_RP_CLIENT_ID'),

    'client_secret' => env('OIDC_RP_CLIENT_SECRET'),

    /*
    | The redirect_uri sent to the provider during the code flow. Leave empty to
    | derive it from the `login.callback` route, so a configured `routes.prefix`
    | can never make the advertised URL diverge from the registered route. Set an
    | absolute URL only to override that (e.g. behind a proxy where the app URL
    | is not the public one).
    */
    'redirect_uri' => env('OIDC_RP_REDIRECT_URI'),

    'scopes' => ['openid', 'profile', 'email'],

    'login_guard' => env('OIDC_RP_LOGIN_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Settings applied to every endpoint the package registers. `prefix` is
    | prepended to each route's path; `middleware` is appended to each route's
    | middleware. Routes are only registered when `enabled` is true.
    |
    */

    'routes' => [
        'prefix' => '',
        'middleware' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route handlers
    |--------------------------------------------------------------------------
    |
    | Sparse, per-endpoint overrides keyed by route name (`login`,
    | `login.callback`, `logout`, `oidc.backchannel-logout`). Each entry may set
    | any of `route` (URI path), `controller`, or `middleware`; omitted keys fall
    | back to the package default, and a `middleware` override replaces (does not
    | merge with) the default. Set an entry to `false` to disable that endpoint.
    | The HTTP verb is intrinsic to the endpoint and lives in code, not here.
    |
    */

    'handlers' => [],

    'redirect_after_login' => env('OIDC_RP_HOME', '/dashboard'),

    'post_logout_redirect_uri' => env('OIDC_RP_POST_LOGOUT_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Discovery cache
    |--------------------------------------------------------------------------
    |
    | The provider's discovery document and JWKS are cached for this many
    | seconds to avoid an HTTP round-trip on every authentication request.
    |
    */

    'discovery_cache_ttl' => (int) env('OIDC_RP_DISCOVERY_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Clock skew
    |--------------------------------------------------------------------------
    |
    | Allowed leeway, in seconds, when validating the id_token `exp`/`nbf`/`iat`
    | claims.
    |
    */

    'leeway' => (int) env('OIDC_RP_LEEWAY', 60),

    /*
    |--------------------------------------------------------------------------
    | Back-channel logout
    |--------------------------------------------------------------------------
    |
    | When enabled, the package registers an endpoint that accepts OIDC
    | back-channel logout tokens from the provider and terminates the
    | matching local session(s).
    |
    */

    'backchannel_logout' => [
        'enabled' => env('OIDC_RP_BACKCHANNEL_LOGOUT_ENABLED', false),
        'auto_middleware' => true,
        'middleware_group' => 'web',
        'retention_minutes' => (int) env('OIDC_RP_BACKCHANNEL_LOGOUT_RETENTION', (int) env('SESSION_LIFETIME', 120)),
    ],
];
