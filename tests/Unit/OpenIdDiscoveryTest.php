<?php

namespace ItsmeLaravel\Itsme\Tests\Unit;

use ItsmeLaravel\Itsme\Exceptions\ItsmeException;
use ItsmeLaravel\Itsme\Services\OpenIdDiscovery;
use ItsmeLaravel\Itsme\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class OpenIdDiscoveryTest extends TestCase
{
    protected OpenIdDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('itsme.discovery_url', 'https://idp.itsme.be/.well-known/openid-configuration');
        Config::set('itsme.environment', 'sandbox');

        $this->discovery = new OpenIdDiscovery();
    }

    public function test_discovers_openid_configuration(): void
    {
        $config = [
            'issuer' => 'https://idp.itsme.be',
            'authorization_endpoint' => 'https://idp.itsme.be/authorize',
            'token_endpoint' => 'https://idp.itsme.be/token',
            'userinfo_endpoint' => 'https://idp.itsme.be/userinfo',
            'jwks_uri' => 'https://idp.itsme.be/.well-known/jwks.json',
        ];

        Http::fake([
            '*/.well-known/openid-configuration' => Http::response($config),
        ]);

        $result = $this->discovery->getConfiguration();

        $this->assertEquals($config, $result);
    }

    public function test_gets_authorization_endpoint(): void
    {
        $config = [
            'authorization_endpoint' => 'https://idp.itsme.be/authorize',
        ];

        Http::fake([
            '*/.well-known/openid-configuration' => Http::response($config),
        ]);

        $endpoint = $this->discovery->getAuthorizationEndpoint();

        $this->assertEquals('https://idp.itsme.be/authorize', $endpoint);
    }

    public function test_gets_token_endpoint(): void
    {
        $config = [
            'token_endpoint' => 'https://idp.itsme.be/token',
        ];

        Http::fake([
            '*/.well-known/openid-configuration' => Http::response($config),
        ]);

        $endpoint = $this->discovery->getTokenEndpoint();

        $this->assertEquals('https://idp.itsme.be/token', $endpoint);
    }

    public function test_gets_userinfo_endpoint(): void
    {
        $config = [
            'userinfo_endpoint' => 'https://idp.itsme.be/userinfo',
        ];

        Http::fake([
            '*/.well-known/openid-configuration' => Http::response($config),
        ]);

        $endpoint = $this->discovery->getUserInfoEndpoint();

        $this->assertEquals('https://idp.itsme.be/userinfo', $endpoint);
    }

    public function test_caches_configuration(): void
    {
        $config = [
            'authorization_endpoint' => 'https://idp.itsme.be/authorize',
        ];

        Http::fake([
            '*/.well-known/openid-configuration' => Http::response($config),
        ]);

        Cache::flush();

        // First call
        $this->discovery->getConfiguration();

        // Second call should use cache
        $this->discovery->getConfiguration();

        // Should only make one HTTP request
        Http::assertSentCount(1);
    }

    public function test_throws_exception_on_discovery_failure(): void
    {
        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([], 500),
        ]);

        $this->expectException(ItsmeException::class);

        $this->discovery->getConfiguration();
    }

    public function test_throws_exception_on_missing_endpoint(): void
    {
        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([]),
        ]);

        $this->expectException(ItsmeException::class);
        $this->expectExceptionMessage('Authorization endpoint not found');

        $this->discovery->getAuthorizationEndpoint();
    }
}

