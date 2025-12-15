<?php

namespace ItsmeLaravel\Itsme\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use ItsmeLaravel\Itsme\Exceptions\InvalidTokenException;

class TokenValidator
{
    /**
     * Validate an ID token.
     */
    public function validateIdToken(string $idToken, string $nonce): array
    {
        // Decode the token without verification first to get the header
        $parts = explode('.', $idToken);
        
        if (count($parts) !== 3) {
            throw new InvalidTokenException('Invalid token format');
        }

        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        if (!$payload) {
            throw new InvalidTokenException('Failed to decode token payload');
        }

        // Verify expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new InvalidTokenException('Token expired');
        }

        // Verify issuer
        $expectedIssuer = $this->getExpectedIssuer();
        if (isset($payload['iss']) && $payload['iss'] !== $expectedIssuer) {
            throw new InvalidTokenException('Invalid issuer: ' . ($payload['iss'] ?? 'missing'));
        }

        // Verify audience
        $clientId = config('itsme.client_id');
        $audience = $payload['aud'] ?? null;
        
        if (is_array($audience)) {
            if (!in_array($clientId, $audience)) {
                throw new InvalidTokenException('Invalid audience');
            }
        } elseif ($audience !== $clientId) {
            throw new InvalidTokenException('Invalid audience');
        }

        // Verify nonce
        if (isset($payload['nonce']) && $payload['nonce'] !== $nonce) {
            throw new InvalidTokenException('Invalid nonce');
        }

        // Verify signature if enabled
        if (config('itsme.verify_token_signature', true)) {
            $this->verifySignature($idToken, $header);
        }

        return $payload;
    }

    /**
     * Verify the JWT signature.
     */
    protected function verifySignature(string $idToken, array $header): void
    {
        $kid = $header['kid'] ?? null;
        
        if (!$kid) {
            throw new InvalidTokenException('Key ID (kid) not found in token header');
        }

        $jwks = $this->getJwks();
        $publicKey = $this->findPublicKey($jwks, $kid);

        if (!$publicKey) {
            throw new InvalidTokenException('Public key not found for kid: ' . $kid);
        }

        try {
            // Use Firebase JWT library to verify
            JWT::decode($idToken, new Key($publicKey, $header['alg'] ?? 'RS256'));
        } catch (\Exception $e) {
            throw new InvalidTokenException('Token signature verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Get JWKS (JSON Web Key Set).
     */
    protected function getJwks(): array
    {
        $discovery = app(OpenIdDiscovery::class);
        $jwksUri = $discovery->getJwksUri();

        $response = Http::timeout(10)->get($jwksUri);

        if (!$response->successful()) {
            throw new InvalidTokenException('Failed to retrieve JWKS');
        }

        return $response->json();
    }

    /**
     * Find the public key for a given key ID.
     */
    protected function findPublicKey(array $jwks, string $kid): ?string
    {
        if (!isset($jwks['keys']) || !is_array($jwks['keys'])) {
            return null;
        }

        foreach ($jwks['keys'] as $key) {
            if (isset($key['kid']) && $key['kid'] === $kid) {
                return $this->convertJwkToPem($key);
            }
        }

        return null;
    }

    /**
     * Convert JWK to PEM format.
     */
    protected function convertJwkToPem(array $jwk): string
    {
        if (!isset($jwk['kty']) || $jwk['kty'] !== 'RSA') {
            throw new InvalidTokenException('Unsupported key type: ' . ($jwk['kty'] ?? 'unknown'));
        }

        if (!isset($jwk['n']) || !isset($jwk['e'])) {
            throw new InvalidTokenException('Invalid JWK: missing n or e');
        }

        $modulus = $this->base64UrlDecode($jwk['n']);
        $exponent = $this->base64UrlDecode($jwk['e']);

        $rsaPublicKey = [
            'modulus' => $modulus,
            'exponent' => $exponent,
        ];

        $publicKey = openssl_pkey_get_public($this->createPemFromModulusAndExponent($rsaPublicKey));

        if (!$publicKey) {
            throw new InvalidTokenException('Failed to create public key from JWK');
        }

        $publicKeyDetails = openssl_pkey_get_details($publicKey);
        
        return $publicKeyDetails['key'] ?? '';
    }

    /**
     * Create PEM from modulus and exponent.
     */
    protected function createPemFromModulusAndExponent(array $rsaPublicKey): string
    {
        $modulus = $rsaPublicKey['modulus'];
        $exponent = $rsaPublicKey['exponent'];

        $modulusLength = strlen($modulus);
        $exponentLength = strlen($exponent);

        // Build the ASN.1 structure
        $asn1 = "\x30" . $this->buildLength($modulusLength + $exponentLength + 8);
        $asn1 .= "\x02" . $this->buildLength($modulusLength) . $modulus;
        $asn1 .= "\x02" . $this->buildLength($exponentLength) . $exponent;

        $publicKey = "\x30" . $this->buildLength(strlen($asn1) + 2);
        $publicKey .= "\x00\x00";
        $publicKey .= $asn1;

        $publicKey = base64_encode($publicKey);

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKey, 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Build ASN.1 length encoding.
     */
    protected function buildLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $lengthBytes = '';
        while ($length > 0) {
            $lengthBytes = chr($length & 0xFF) . $lengthBytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($lengthBytes)) . $lengthBytes;
    }

    /**
     * Base64 URL decode.
     */
    protected function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Get the expected issuer.
     */
    protected function getExpectedIssuer(): string
    {
        // Use explicit issuer if set
        if ($issuer = config('itsme.issuer')) {
            return $issuer;
        }

        // Use environment-specific issuer
        $environment = config('itsme.environment', 'sandbox');
        $environments = config('itsme.environments', []);

        if (isset($environments[$environment]['issuer'])) {
            return $environments[$environment]['issuer'];
        }

        // Fallback to discovery
        $discovery = app(OpenIdDiscovery::class);
        return $discovery->getIssuer();
    }
}

