<?php

namespace ItsmeLaravel\Itsme\Tests\Feature;

use ItsmeLaravel\Itsme\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('itsme.client_id', 'test_client_id');
        Config::set('itsme.client_secret', 'test_client_secret');
        Config::set('itsme.redirect', 'http://localhost/itsme/callback');
    }

    public function test_redirect_route_generates_authorization_url(): void
    {
        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://idp.itsme.be/authorize',
                'token_endpoint' => 'https://idp.itsme.be/token',
                'userinfo_endpoint' => 'https://idp.itsme.be/userinfo',
            ]),
        ]);

        $response = $this->get('/itsme/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('idp.itsme.be/authorize', $response->headers->get('Location'));
    }

    public function test_callback_creates_user_on_first_login(): void
    {
        Session::start();
        Session::put('itsme.state', 'test_state');
        Session::put('itsme.nonce', 'test_nonce');
        Session::put('itsme.code_verifier', 'test_verifier');

        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://idp.itsme.be/authorize',
                'token_endpoint' => 'https://idp.itsme.be/token',
                'userinfo_endpoint' => 'https://idp.itsme.be/userinfo',
                'issuer' => 'https://idp.itsme.be',
            ]),
            '*/token' => Http::response([
                'access_token' => 'test_access_token',
                'id_token' => $this->createTestIdToken(),
                'token_type' => 'Bearer',
            ]),
            '*/userinfo' => Http::response([
                'sub' => 'user123',
                'email' => 'test@example.com',
                'given_name' => 'John',
                'family_name' => 'Doe',
                'email_verified' => true,
            ]),
        ]);

        Config::set('itsme.verify_token_signature', false);
        Config::set('itsme.issuer', 'https://idp.itsme.be');

        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        // Create users table if it doesn't exist
        if (!\Schema::hasTable('users')) {
            \Schema::create('users', function ($table) {
                $table->id();
                $table->string('itsme_id')->nullable();
                $table->string('email')->nullable();
                $table->string('name')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->timestamps();
            });
        }

        $response = $this->get('/itsme/callback?code=test_code&state=test_state');

        $this->assertDatabaseHas('users', [
            'itsme_id' => 'user123',
            'email' => 'test@example.com',
        ]);
    }

    public function test_callback_rejects_invalid_state(): void
    {
        Session::start();
        Session::put('itsme.state', 'correct_state');

        $response = $this->get('/itsme/callback?code=test_code&state=wrong_state');

        $response->assertRedirect();
    }

    protected function createTestIdToken(): string
    {
        $payload = [
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'exp' => time() + 3600,
            'iat' => time(),
            'nonce' => 'test_nonce',
        ];

        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payloadEncoded = base64_encode(json_encode($payload));
        $signature = base64_encode('fake_signature');

        return $header . '.' . $payloadEncoded . '.' . $signature;
    }
}

