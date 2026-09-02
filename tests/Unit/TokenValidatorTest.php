<?php

namespace ItsmeLaravel\Itsme\Tests\Unit;

use ItsmeLaravel\Itsme\Exceptions\InvalidTokenException;
use ItsmeLaravel\Itsme\Services\TokenValidator;
use ItsmeLaravel\Itsme\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;

class TokenValidatorTest extends TestCase
{
    protected TokenValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('itsme.client_id', 'test_client_id');
        Config::set('itsme.issuer', 'https://idp.itsme.be');
        Config::set('itsme.verify_token_signature', false); // Claims-only for most unit tests

        $this->validator = new TokenValidator();
    }

    public function test_validates_token_successfully(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
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
            'iat' => time() - 7200,
            'nbf' => time() - 7200,
            'exp' => time() - 3600, // Expired
            'nonce' => 'test_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token expired');

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_missing_expiration(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'nonce' => 'test_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_token_with_invalid_audience(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'wrong_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid audience');

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_missing_audience(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_token_with_invalid_nonce(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'nonce' => 'wrong_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid nonce');

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_missing_nonce(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
        ]);

        $this->expectException(InvalidTokenException::class);

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_missing_issuer(): void
    {
        $idToken = $this->createTestToken([
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_token_with_wrong_issuer(): void
    {
        $idToken = $this->createTestToken([
            'iss' => 'https://evil.example.com',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ]);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid issuer');

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    public function test_rejects_invalid_token_format(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid token format');

        $this->validator->validateIdToken('invalid.token', 'test_nonce');
    }

    public function test_rejects_algorithm_confusion_attack(): void
    {
        // Set up an RSA keypair whose PUBLIC key is exposed via the JWKS endpoint.
        [$privateKey, $publicKey, $jwk] = $this->rsaKeyPair();

        Config::set('itsme.jwks_uri', 'https://idp.test/.well-known/jwks');
        Config::set('itsme.verify_token_signature', true);

        Http::fake([
            'https://idp.test/.well-known/jwks' => Http::response([
                'keys' => [$jwk],
            ]),
        ]);

        // An attacker forges a token with alg=HS256 using the PUBLIC key as HMAC secret.
        $header = $this->b64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT', 'kid' => 'key1']));
        $payload = $this->b64UrlEncode(json_encode([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ]));
        $signature = $this->b64UrlEncode(hash_hmac('sha256', $header . '.' . $payload, $publicKey, true));
        $forgedToken = $header . '.' . $payload . '.' . $signature;

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Unsupported token algorithm');

        $this->validator->validateIdToken($forgedToken, 'test_nonce');
    }

    public function test_accepts_valid_rs256_signature(): void
    {
        [$privateKey, $publicKey, $jwk] = $this->rsaKeyPair();

        Config::set('itsme.jwks_uri', 'https://idp.test/.well-known/jwks');
        Config::set('itsme.verify_token_signature', true);

        Http::fake([
            'https://idp.test/.well-known/jwks' => Http::response([
                'keys' => [$jwk],
            ]),
        ]);

        $header = $this->b64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'key1']));
        $payloadArray = [
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ];
        $payload = $this->b64UrlEncode(json_encode($payloadArray));

        // openssl_pkey_new is unusable in this CI environment; sign with phpseclib3.
        $signature = $privateKey->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256')->sign($header . '.' . $payload);
        $idToken = $header . '.' . $payload . '.' . $this->b64UrlEncode($signature);

        $decoded = $this->validator->validateIdToken($idToken, 'test_nonce');

        $this->assertEquals('user123', $decoded['sub']);
    }

    public function test_rejects_tampered_rs256_signature(): void
    {
        [$privateKey, $publicKey, $jwk] = $this->rsaKeyPair();

        Config::set('itsme.jwks_uri', 'https://idp.test/.well-known/jwks');
        Config::set('itsme.verify_token_signature', true);

        Http::fake([
            'https://idp.test/.well-known/jwks' => Http::response([
                'keys' => [$jwk],
            ]),
        ]);

        $header = $this->b64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'key1']));
        $payload = $this->b64UrlEncode(json_encode([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'user123',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ]));

        // Sign then corrupt the payload
        $forgedPayload = $this->b64UrlEncode(json_encode([
            'iss' => 'https://idp.itsme.be',
            'sub' => 'attacker',
            'aud' => 'test_client_id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'nonce' => 'test_nonce',
        ]));
        $signature = $privateKey->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256')->sign($header . '.' . $forgedPayload);
        $idToken = $header . '.' . $payload . '.' . $this->b64UrlEncode($signature);

        $this->expectException(InvalidTokenException::class);

        $this->validator->validateIdToken($idToken, 'test_nonce');
    }

    protected function createTestToken(array $payload): string
    {
        $header = $this->b64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payloadEncoded = $this->b64UrlEncode(json_encode($payload));
        $signature = $this->b64UrlEncode('fake_signature');

        return $header . '.' . $payloadEncoded . '.' . $signature;
    }

    protected function b64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @return array{0: RSA\PrivateKey, 1: string, 2: array<string, mixed>} [privateKey, publicKeyPem, jwk]
     */
    protected function rsaKeyPair(): array
    {
        $key = RSA::createKey(2048);

        $this->assertNotFalse($key);

        $publicKey = $key->getPublicKey();
        $publicPem = $publicKey->toString('PKCS8');
        $jwk = json_decode($publicKey->toString('JWK'), true)['keys'][0] ?? [];

        $jwk['alg'] = 'RS256';
        $jwk['use'] = 'sig';
        $jwk['kid'] = 'key1';

        return [
            $key,
            $publicPem,
            $jwk,
        ];
    }
}