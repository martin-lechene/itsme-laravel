<?php

namespace ItsmeLaravel\Itsme\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ItsmeLaravel\Itsme\Exceptions\ItsmeException;

class OpenIdDiscovery
{
    /**
     * Cache key for OpenID configuration.
     */
    protected const CACHE_KEY = 'itsme.openid_config';

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get the OpenID Connect configuration.
     */
    public function getConfiguration(): array
    {
        $discoveryUrl = $this->getDiscoveryUrl();

        return Cache::remember(self::CACHE_KEY . '.' . md5($discoveryUrl), self::CACHE_TTL, function () use ($discoveryUrl) {
            return $this->discover($discoveryUrl);
        });
    }

    /**
     * Discover OpenID Connect configuration from the discovery endpoint.
     */
    public function discover(string $discoveryUrl): array
    {
        try {
            $response = Http::timeout(10)->get($discoveryUrl);

            if (!$response->successful()) {
                throw new ItsmeException('Failed to discover OpenID configuration: ' . $response->status());
            }

            $config = $response->json();

            if (empty($config)) {
                throw new ItsmeException('Empty OpenID configuration received');
            }

            return $config;
        } catch (\Exception $e) {
            throw new ItsmeException('OpenID discovery failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the authorization endpoint URL.
     */
    public function getAuthorizationEndpoint(): string
    {
        $config = $this->getConfiguration();

        if (!isset($config['authorization_endpoint'])) {
            throw new ItsmeException('Authorization endpoint not found in OpenID configuration');
        }

        return $config['authorization_endpoint'];
    }

    /**
     * Get the token endpoint URL.
     */
    public function getTokenEndpoint(): string
    {
        $config = $this->getConfiguration();

        if (!isset($config['token_endpoint'])) {
            throw new ItsmeException('Token endpoint not found in OpenID configuration');
        }

        return $config['token_endpoint'];
    }

    /**
     * Get the UserInfo endpoint URL.
     */
    public function getUserInfoEndpoint(): string
    {
        $config = $this->getConfiguration();

        if (!isset($config['userinfo_endpoint'])) {
            throw new ItsmeException('UserInfo endpoint not found in OpenID configuration');
        }

        return $config['userinfo_endpoint'];
    }

    /**
     * Get the JWKS URI.
     */
    public function getJwksUri(): string
    {
        $config = $this->getConfiguration();

        if (!isset($config['jwks_uri'])) {
            throw new ItsmeException('JWKS URI not found in OpenID configuration');
        }

        return $config['jwks_uri'];
    }

    /**
     * Get the issuer identifier.
     */
    public function getIssuer(): string
    {
        $config = $this->getConfiguration();

        if (!isset($config['issuer'])) {
            throw new ItsmeException('Issuer not found in OpenID configuration');
        }

        return $config['issuer'];
    }

    /**
     * Clear the cached configuration.
     */
    public function clearCache(): void
    {
        $discoveryUrl = $this->getDiscoveryUrl();
        Cache::forget(self::CACHE_KEY . '.' . md5($discoveryUrl));
    }

    /**
     * Get the discovery URL.
     */
    protected function getDiscoveryUrl(): string
    {
        // Use explicit discovery URL if set
        if ($discoveryUrl = config('itsme.discovery_url')) {
            return $discoveryUrl;
        }

        // Use environment-specific discovery URL
        $environment = config('itsme.environment', 'sandbox');
        $environments = config('itsme.environments', []);

        if (isset($environments[$environment]['discovery_url'])) {
            return $environments[$environment]['discovery_url'];
        }

        throw new ItsmeException('Discovery URL not configured');
    }
}

