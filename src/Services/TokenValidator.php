<?php

namespace ItsmeLaravel\Itsme\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ItsmeLaravel\Itsme\Exceptions\InvalidTokenException;

class TokenValidator
{
    /**
     * Validate an ID token.
     *
     * Strict OIDC validation: signature (when enabled) is checked first,
     * then exp/iat/nbf, issuer, audience and nonce are all REQUIRED.
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

        if (! is_array($payload)) {
            throw new InvalidTokenException('Failed to decode token payload');
        }

        // Verify signature FIRST so we never trust attacker-controlled claims
        $verify = (bool) config('itsme.verify_token_signature', true);
        if ($verify) {
            $this->verifySignature($idToken, is_array($header) ? $header : []);
        } elseif (! app()->environment(['local', 'testing'])) {
            throw new InvalidTokenException('Signature verification cannot be disabled outside local/testing');
        }

        $leeway = (int) config('itsme.clock_tolerance', 60);
        $now = time();

        // exp is required
        if (! isset($payload['exp']) || ! is_int($payload['exp'])) {
            throw new InvalidTokenException('Missing expiration (exp) claim');
        }
        if ($payload['exp'] < $now - $leeway) {
            throw new InvalidTokenException('Token expired');
        }

        // iat is required
        if (! isset($payload['iat']) || ! is_int($payload['iat'])) {
            throw new InvalidTokenException('Missing issued-at (iat) claim');
        }
        if ($payload['iat'] > $now + $leeway) {
            throw new InvalidTokenException('Token issued in the future');
        }

        // nbf is required by OIDC for ID tokens
        if (! isset($payload['nbf']) || ! is_int($payload['nbf'])) {
            throw new InvalidTokenException('Missing not-before (nbf) claim');
        }
        if ($payload['nbf'] > $now + $leeway) {
            throw new InvalidTokenException('Token not valid yet (nbf)');
        }

        // issuer is required
        if (! isset($payload['iss']) || ! is_string($payload['iss'])) {
            throw new InvalidTokenException('Missing issuer (iss) claim');
        }
        $expectedIssuer = $this->getExpectedIssuer();
        if (! hash_equals($expectedIssuer, $payload['iss'])) {
            throw new InvalidTokenException('Invalid issuer');
        }

        // audience is required and must contain our client id
        $clientId = config('itsme.client_id');
        if ($clientId === null || $clientId === '') {
            throw new InvalidTokenException('itsme.client_id is not configured');
        }
        if (! isset($payload['aud'])) {
            throw new InvalidTokenException('Missing audience (aud) claim');
        }
        $audience = $payload['aud'];
        $validAudience = is_array($audience)
            ? in_array($clientId, $audience, true)
            : hash_equals((string) $clientId, (string) $audience);
        if (! $validAudience) {
            throw new InvalidTokenException('Invalid audience');
        }

        // nonce is required and compared in constant time
        if (! isset($payload['nonce']) || ! is_string($payload['nonce'])) {
            throw new InvalidTokenException('Missing nonce claim');
        }
        if (! hash_equals($nonce, $payload['nonce'])) {
            throw new InvalidTokenException('Invalid nonce');
        }

        return $payload;
    }

    /**
     * Verify the JWT signature using the JWKS.
     */
    protected function verifySignature(string $idToken, array $header): void
    {
        $allowedAlgorithms = config('itsme.allowed_algorithms', ['RS256']);

        $alg = $header['alg'] ?? null;
        if (! is_string($alg) || ! in_array($alg, $allowedAlgorithms, true)) {
            throw new InvalidTokenException('Unsupported token algorithm');
        }

        $kid = $header['kid'] ?? null;

        if (! $kid) {
            throw new InvalidTokenException('Key ID (kid) not found in token header');
        }

        $jwks = $this->getJwks();
        $publicKey = $this->findPublicKey($jwks, $kid, $alg);

        if (! $publicKey) {
            throw new InvalidTokenException('Public key not found for kid: ' . $kid);
        }

        try {
            // The expected algorithm is enforced from the whitelist, never from the token header.
            JWT::decode($idToken, new Key($publicKey, $alg));
        } catch (\Exception $e) {
            throw new InvalidTokenException('Token signature verification failed');
        }
    }

    /**
     * Get JWKS (JSON Web Key Set), cached for a short TTL.
     */
    protected function getJwks(): array
    {
        $jwksUri = config('itsme.jwks_uri');
        if (! is_string($jwksUri) || $jwksUri === '') {
            $discovery = app(OpenIdDiscovery::class);
            $jwksUri = $discovery->getJwksUri();
        }

        $cacheKey = 'itsme.jwks.' . md5($jwksUri);
        $ttl = (int) config('itsme.jwks_cache_ttl', 300);

        return Cache::remember($cacheKey, $ttl, function () use ($jwksUri) {
            $response = Http::timeout(10)->get($jwksUri);

            if (! $response->successful()) {
                throw new InvalidTokenException('Failed to retrieve JWKS');
            }

            $jwks = $response->json();

            return is_array($jwks) ? $jwks : ['keys' => []];
        });
    }

    /**
     * Find the public key for a given key ID.
     */
    protected function findPublicKey(array $jwks, string $kid, string $alg): ?string
    {
        if (! isset($jwks['keys']) || ! is_array($jwks['keys'])) {
            return null;
        }

        foreach ($jwks['keys'] as $key) {
            if (! is_array($key) || ($key['kid'] ?? null) !== $kid) {
                continue;
            }
            // Reject keys that disagree on usage/algorithm when declared
            if (isset($key['use']) && $key['use'] !== 'sig') {
                continue;
            }
            if (isset($key['alg']) && $key['alg'] !== $alg) {
                continue;
            }
            if (($key['kty'] ?? null) !== 'RSA') {
                continue;
            }

            return $this->convertJwkToPem($key);
        }

        return null;
    }

    /**
     * Convert JWK to PEM format (SubjectPublicKeyInfo).
     */
    protected function convertJwkToPem(array $jwk): string
    {
        if (($jwk['kty'] ?? null) !== 'RSA') {
            throw new InvalidTokenException('Unsupported key type: ' . ($jwk['kty'] ?? 'unknown'));
        }

        if (! isset($jwk['n']) || ! isset($jwk['e'])) {
            throw new InvalidTokenException('Invalid JWK: missing n or e');
        }

        $modulus = $this->base64UrlDecode((string) $jwk['n']);
        $exponent = $this->base64UrlDecode((string) $jwk['e']);

        if ($modulus === '' || $exponent === '') {
            throw new InvalidTokenException('Invalid JWK: empty n or e');
        }

        // Preferred: phpseclib3 (correct DER encoding, no hand-rolled ASN.1).
        if (class_exists(\phpseclib3\Crypt\PublicKeyLoader::class) &&
            class_exists(\phpseclib3\Math\BigInteger::class)) {
            try {
                $rsa = \phpseclib3\Crypt\PublicKeyLoader::loadPublicKey([
                    'e' => new \phpseclib3\Math\BigInteger($exponent, 256),
                    'n' => new \phpseclib3\Math\BigInteger($modulus, 256),
                ]);

                return $rsa->toString('PKCS8');
            } catch (\Throwable $e) {
                throw new InvalidTokenException('Failed to create public key from JWK');
            }
        }

        // Fallback: correct SubjectPublicKeyInfo builder.
        return $this->createPemFromModulusAndExponent([
            'modulus' => $modulus,
            'exponent' => $exponent,
        ]);
    }

    /**
     * Create a SubjectPublicKeyInfo PEM from modulus and exponent.
     */
    protected function createPemFromModulusAndExponent(array $rsaPublicKey): string
    {
        $modulus = $rsaPublicKey['modulus'];
        $exponent = $rsaPublicKey['exponent'];

        // DER INTEGER requires a leading 0x00 when the high bit is set
        if (ord($modulus[0]) & 0x80) {
            $modulus = "\x00" . $modulus;
        }
        if (ord($exponent[0]) & 0x80) {
            $exponent = "\x00" . $exponent;
        }

        // RSAPublicKey ::= SEQUENCE { modulus INTEGER, publicExponent INTEGER }
        $rsa = "\x02" . $this->buildLength(strlen($modulus)) . $modulus;
        $rsa .= "\x02" . $this->buildLength(strlen($exponent)) . $exponent;
        $rsa = "\x30" . $this->buildLength(strlen($rsa)) . $rsa;

        // AlgorithmIdentifier: rsaEncryption (1.2.840.113549.1.1.1) + NULL params
        $algId = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

        // SubjectPublicKeyInfo ::= SEQUENCE { algorithm, BIT STRING subjectPublicKey }
        $bitString = "\x00" . $rsa;
        $spki = $algId . "\x03" . $this->buildLength(strlen($bitString)) . $bitString;
        $spki = "\x30" . $this->buildLength(strlen($spki)) . $spki;

        $base64 = base64_encode($spki);

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($base64, 64, "\n") . "-----END PUBLIC KEY-----\n";
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