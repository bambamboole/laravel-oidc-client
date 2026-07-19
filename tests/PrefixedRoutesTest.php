<?php

declare(strict_types=1);

namespace Bambamboole\LaravelOidcClient\Tests;

use Bambamboole\LaravelOidcClient\Tests\Support\PrefixedRoutesTestCase;
use Illuminate\Support\Facades\Http;

class PrefixedRoutesTest extends PrefixedRoutesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('oidc-client.issuer', 'https://id.example.com');
        config()->set('oidc-client.client_id', 'client-123');

        Http::fake([
            'https://id.example.com/.well-known/openid-configuration' => Http::response([
                'issuer' => 'https://id.example.com',
                'authorization_endpoint' => 'https://id.example.com/oauth/authorize',
                'token_endpoint' => 'https://id.example.com/oauth/token',
                'jwks_uri' => 'https://id.example.com/.well-known/jwks.json',
            ]),
        ]);
    }

    public function test_it_registers_every_endpoint_under_the_configured_prefix(): void
    {
        $this->assertSame('/sso/login', parse_url(route('login'), PHP_URL_PATH));
        $this->assertSame('/sso/login/callback', parse_url(route('login.callback'), PHP_URL_PATH));
        $this->assertSame('/sso/logout', parse_url(route('logout'), PHP_URL_PATH));
        $this->assertSame('/sso/oidc/backchannel-logout', parse_url(route('oidc.backchannel-logout'), PHP_URL_PATH));
    }

    public function test_the_advertised_redirect_uri_tracks_the_prefix(): void
    {
        $response = $this->get(route('login'));

        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $params);

        $this->assertSame(route('login.callback'), $params['redirect_uri']);
        $this->assertStringContainsString('/sso/login/callback', (string) $params['redirect_uri']);
    }
}
