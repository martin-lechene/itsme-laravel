<?php

namespace ItsmeLaravel\Itsme\Tests\Unit;

use ItsmeLaravel\Itsme\Exceptions\AuthenticationFailedException;
use ItsmeLaravel\Itsme\Exceptions\InvalidStateException;
use ItsmeLaravel\Itsme\Services\ItsmeService;
use ItsmeLaravel\Itsme\Services\OpenIdDiscovery;
use ItsmeLaravel\Itsme\Services\TokenValidator;
use ItsmeLaravel\Itsme\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class ItsmeServiceTest extends TestCase
{
    protected ItsmeService $service;
    protected TokenValidator $tokenValidator;
    protected OpenIdDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('itsme.client_id', 'test_client_id');
        Config::set('itsme.client_secret', 'test_client_secret');
        Config::set('itsme.redirect', 'http://localhost/itsme/callback');
        Config::set('itsme.scopes', ['openid', 'profile', 'email']);
        Config::set('itsme.use_pkce', true);

        $this->tokenValidator = $this->createMock(TokenValidator::class);
        $this->discovery = $this->createMock(OpenIdDiscovery::class);
        $this->service = new ItsmeService($this->tokenValidator, $this->discovery);
    }

    public function test_generates_authorization_url(): void
    {
        $this->discovery
            ->expects($this->once())
            ->method('getAuthorizationEndpoint')
            ->willReturn('https://idp.itsme.be/authorize');

        $url = $this->service->getAuthorizationUrl();

        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('nonce=', $url);
        $this->assertStringContainsString('code_challenge=', $url);
        $this->assertStringContainsString('code_challenge_method=S256', $url);
        $this->assertStringStartsWith('https://idp.itsme.be/authorize', $url);
    }

    public function test_generates_authorization_url_without_pkce(): void
    {
        Config::set('itsme.use_pkce', false);

        $this->discovery
            ->expects($this->once())
            ->method('getAuthorizationEndpoint')
            ->willReturn('https://idp.itsme.be/authorize');

        $url = $this->service->getAuthorizationUrl();

        $this->assertStringNotContainsString('code_challenge', $url);
        $this->assertStringNotContainsString('code_challenge_method', $url);
    }

    public function test_stores_state_and_nonce_in_session(): void
    {
        Session::start();

        $this->discovery
            ->method('getAuthorizationEndpoint')
            ->willReturn('https://idp.itsme.be/authorize');

        $this->service->getAuthorizationUrl();

        $this->assertNotNull(Session::get('itsme.state'));
        $this->assertNotNull(Session::get('itsme.nonce'));
        $this->assertIsString(Session::get('itsme.state'));
        $this->assertIsString(Session::get('itsme.nonce'));
    }

    public function test_handles_callback_successfully(): void
    {
        Session::start();
        Session::put('itsme.state', 'test_state');
        Session::put('itsme.nonce', 'test_nonce');
        Session::put('itsme.code_verifier', 'test_verifier');

        $request = new Request([
            'code' => 'test_code',
            'state' => 'test_state',
        ]);

        Http::fake([
            '*/token' => Http::response([
                'access_token' => 'test_access_token',
                'id_token' => 'test_id_token',
                'token_type' => 'Bearer',
            ]),
            '*/userinfo' => Http::response([
                'sub' => 'user123',
                'email' => 'test@example.com',
                'given_name' => 'John',
                'family_name' => 'Doe',
            ]),
        ]);

        $this->discovery
            ->method('getTokenEndpoint')
            ->willReturn('https://idp.itsme.be/token');

        $this->discovery
            ->method('getUserInfoEndpoint')
            ->willReturn('https://idp.itsme.be/userinfo');

        $this->tokenValidator
            ->expects($this->once())
            ->method('validateIdToken')
            ->with('test_id_token', 'test_nonce')
            ->willReturn([
                'sub' => 'user123',
                'email' => 'test@example.com',
            ]);

        $userInfo = $this->service->handleCallback($request);

        $this->assertIsArray($userInfo);
        $this->assertEquals('user123', $userInfo['sub']);
        $this->assertEquals('test@example.com', $userInfo['email']);
    }

    public function test_handles_callback_with_invalid_state(): void
    {
        Session::start();
        Session::put('itsme.state', 'correct_state');

        $request = new Request([
            'code' => 'test_code',
            'state' => 'wrong_state',
        ]);

        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('Invalid state parameter');

        $this->service->handleCallback($request);
    }

    public function test_handles_callback_with_error(): void
    {
        Session::start();
        Session::put('itsme.state', 'test_state');

        $request = new Request([
            'error' => 'access_denied',
            'error_description' => 'User denied access',
        ]);

        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessage('L\'utilisateur a refusé l\'autorisation');

        $this->service->handleCallback($request);
    }

    public function test_handles_callback_without_code(): void
    {
        Session::start();
        Session::put('itsme.state', 'test_state');

        $request = new Request([
            'state' => 'test_state',
        ]);

        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessage('Authorization code missing');

        $this->service->handleCallback($request);
    }
}

