<?php

declare(strict_types=1);

namespace Bambamboole\LaravelOidcClient\Discovery;

use Bambamboole\LaravelOidcClient\Exceptions\OidcClientException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;

class OidcDiscovery
{
    public function __construct(
        private readonly Http $http,
        private readonly Cache $cache,
    ) {}

    public function metadata(): ProviderMetadata
    {
        $issuer = $this->issuer();

        $doc = $this->cache->remember(
            'oidc-client:discovery:'.$issuer,
            $this->ttl(),
            fn (): array => $this->http->get($issuer.'/.well-known/openid-configuration')->throw()->json(),
        );

        return ProviderMetadata::fromArray($doc);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function jwks(): array
    {
        $metadata = $this->metadata();

        return $this->cache->remember(
            'oidc-client:jwks:'.$metadata->issuer,
            $this->ttl(),
            fn (): array => $this->http->get($metadata->jwksUri)->throw()->json('keys', []),
        );
    }

    private function issuer(): string
    {
        $issuer = config('oidc-client.issuer');

        if (! is_string($issuer) || $issuer === '') {
            throw new OidcClientException('No OIDC issuer has been configured (oidc-client.issuer).');
        }

        return rtrim($issuer, '/');
    }

    private function ttl(): int
    {
        return (int) config('oidc-client.discovery_cache_ttl', 3600);
    }
}
