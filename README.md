# laravel-oidc-client

An OpenID Connect (OIDC) **relying party** for Laravel — log your users in through
any OIDC provider using the real Authorization-Code + PKCE flow, with strict
`id_token` validation against the provider's JWKS.

This is the client-side companion to
[`bambamboole/laravel-oidc`](https://github.com/bambamboole/laravel-oidc) (the OIDC
provider). The two are deliberately separate packages: an app that only needs to
*consume* an identity provider should not pull in a full OAuth2 authorization server,
TOTP, QR codes, and WebAuthn. Point this package at your own `laravel-oidc` provider
for self-SSO, or at any third-party IdP (Keycloak, Auth0, Okta, …).

> **Status:** scaffold. The relying-party module (discovery, PKCE, callback,
> `IdTokenValidator`, guard login) is being implemented as Phase 1b of the
> [self-SSO design](https://github.com/bambamboole/laravel-oidc/blob/main/docs/self-sso-and-auth-engine-design.md).

## Requirements

- PHP `^8.4`
- Laravel `^11.0 || ^12.0 || ^13.0`

## Installation

```bash
composer require bambamboole/laravel-oidc-client

# Optional: publish the config
php artisan vendor:publish --tag=oidc-client-config
```

The service provider is auto-discovered.

## Configuration

See `config/oidc-client.php`. Set the provider `issuer`, your registered
`client_id` / `client_secret` / `redirect_uri`, the `scopes` you request, and the
`login_guard` the resolved user is logged into.

## Development

```bash
composer install
composer check   # pint --test, phpstan, pest
```

Tests run in isolation through Orchestra Testbench — no external OIDC provider is
required.

## License

MIT.
