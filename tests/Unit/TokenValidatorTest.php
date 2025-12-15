<?php

namespace ItsmeLaravel\Itsme\Tests\Unit;

use ItsmeLaravel\Itsme\Exceptions\InvalidTokenException;
use ItsmeLaravel\Itsme\Services\OpenIdDiscovery;
use ItsmeLaravel\Itsme\Services\TokenValidator;
use ItsmeLaravel\Itsme\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class TokenValidatorTest extends TestCase
{
    protected TokenValidator $validator;
    protected OpenIdDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('itsme.client_id', 'test_client_id');
        Config::set('itsme.verify_token_signature', false); // Disable for unit tests

        $this->discovery = $this->createMock(OpenIdDiscovery::class);
        $this->validator = new TokenValidator();
    }

    public function test_validates_token_successfully(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ]);

        $payload = $this->validator->validateIdToken($idToken, 'test_nonce');

        $this->assertEquals('user123', $payload['sub']);
        $this->assertEquals('test_client_id', $payload['aud']);
    }

    public function test_rejects_expired_token(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'exp' => time() - 3600, // Expired
            'nonce' => 'test_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token expired');

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_token_with_invalid_audience(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'wrong_client_id',
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid audience');

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_token_with_invalid_nonce(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'exp' => time() + 3600,
            'nonce' => 'wrong_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid nonce');

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_invalid_token_format(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid token format');

        $this->validator->validateIdToken('invalid.token', 'test_nonce');
    }

    protected function createTestToken(array $payload): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payloadEncoded = base64_encode(json_encode($payload));
        $signature = base64_encode('fake_signature');

        return $header . '.' . $payloadEncoded . '.' . $signature;
    }
}

