<?php

declare(strict_types=1);

use Bambamboole\LaravelOidcClient\Discovery\OidcDiscovery;
use Bambamboole\LaravelOidcClient\Exceptions\OidcClientException;
use Bambamboole\LaravelOidcClient\Facades\OidcClient;
use Bambamboole\LaravelOidcClient\Testing\OidcClientFake;
use Bambamboole\LaravelOidcClient\Token\IdTokenValidator;
use Bambamboole\LaravelOidcClient\Token\LogoutTokenValidator;
use Illuminate\Support\Facades\Http;

it('installs the fake and stubs discovery, jwks and token endpoints', function () {
    OidcClient::fake();

    expect(config('oidc-client.issuer'))->toBe('https://oidc.test')
        ->and(config('oidc-client.client_id'))->toBe('oidc-client-test');
});

it('mints an id_token the real validator accepts', function () {
    $fake = OidcClient::fake();

    $claims = app(IdTokenValidator::class)->validate($fake->idToken(['sub' => '42']), OidcClientFake::NONCE);

    expect($claims['sub'])->toBe('42')
        ->and($claims['iss'])->toBe('https://oidc.test');
});

it('mints a logout_token the real validator accepts', function () {
    $fake = OidcClient::fake();

    $result = app(LogoutTokenValidator::class)->validate($fake->logoutToken(['sub' => '42', 'sid' => 's1']));

    expect($result['sid'])->toBe('s1')
        ->and($result['sub'])->toBe('42');
});

it('honors an issuer configured before fake() and resets the discovery singleton', function () {
    config()->set('oidc-client.issuer', 'https://custom.test');

    // Resolve discovery first so a stale in-memory metadata memo would survive
    // without the fake's lifecycle reset.
    app(OidcDiscovery::class);

    $fake = OidcClient::fake();

    expect(config('oidc-client.issuer'))->toBe('https://custom.test');

    $claims = app(IdTokenValidator::class)->validate($fake->idToken(), OidcClientFake::NONCE);
    expect($claims['iss'])->toBe('https://custom.test');
});

it('fails the token exchange with the configured status', function () {
    OidcClient::fake()->failTokenExchange(400);

    expect(Http::get('https://oidc.test/oauth/token')->status())->toBe(400);
});

it('drops the end_session_endpoint from discovery', function () {
    OidcClient::fake()->withoutEndSessionEndpoint();

    expect(Http::get('https://oidc.test/.well-known/openid-configuration')->json())
        ->not->toHaveKey('end_session_endpoint');
});

it('mints an id_token the validator rejects when signed by a rogue provider', function () {
    $fake = OidcClient::fake()->withInvalidSignature();

    app(IdTokenValidator::class)->validate($fake->idToken(), OidcClientFake::NONCE);
})->throws(OidcClientException::class);
