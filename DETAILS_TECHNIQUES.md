# Détails Techniques - Package Laravel Itsme

## 🔍 Détails d'Implémentation Techniques

### 1. Découverte OpenID Connect

#### Endpoint de Découverte
```
GET /.well-known/openid-configuration
```

#### Réponse Type
```json
{
  "issuer": "https://idp.itsme.be",
  "authorization_endpoint": "https://idp.itsme.be/authorize",
  "token_endpoint": "https://idp.itsme.be/token",
  "userinfo_endpoint": "https://idp.itsme.be/userinfo",
  "jwks_uri": "https://idp.itsme.be/.well-known/jwks.json",
  "response_types_supported": ["code"],
  "subject_types_supported": ["pairwise"],
  "id_token_signing_alg_values_supported": ["RS256"],
  "scopes_supported": ["openid", "profile", "email", "phone"],
  "claims_supported": [
    "sub",
    "email",
    "email_verified",
    "given_name",
    "family_name",
    "name",
    "phone_number"
  ]
}
```

#### Implémentation PHP
```php
class OpenIdDiscovery
{
    public function discover(string $discoveryUrl): array
    {
        $response = Http::get($discoveryUrl);
        
        if (!$response->successful()) {
            throw new ItsmeException('Failed to discover OpenID configuration');
        }
        
        return $response->json();
    }
    
    public function getAuthorizationEndpoint(array $config): string
    {
        return $config['authorization_endpoint'] ?? null;
    }
    
    public function getTokenEndpoint(array $config): string
    {
        return $config['token_endpoint'] ?? null;
    }
    
    public function getUserInfoEndpoint(array $config): string
    {
        return $config['userinfo_endpoint'] ?? null;
    }
}
```

---

### 2. Flux d'Autorisation (Authorization Code Flow)

#### Étape 1 : Génération de l'URL d'Autorisation

```php
public function getAuthorizationUrl(): string
{
    $state = $this->generateState();
    $nonce = $this->generateNonce();
    
    // PKCE
    $codeVerifier = $this->generateCodeVerifier();
    $codeChallenge = $this->generateCodeChallenge($codeVerifier);
    
    // Stocker en session
    session()->put('itsme.state', $state);
    session()->put('itsme.nonce', $nonce);
    session()->put('itsme.code_verifier', $codeVerifier);
    
    $params = [
        'response_type' => 'code',
        'client_id' => config('itsme.client_id'),
        'redirect_uri' => config('itsme.redirect'),
        'scope' => implode(' ', config('itsme.scopes')),
        'state' => $state,
        'nonce' => $nonce,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
    ];
    
    $authEndpoint = $this->getAuthorizationEndpoint();
    
    return $authEndpoint . '?' . http_build_query($params);
}
```

#### Étape 2 : Callback et Échange du Code

```php
public function handleCallback(Request $request): User
{
    // Vérifier le state
    $state = $request->get('state');
    if ($state !== session()->get('itsme.state')) {
        throw new InvalidStateException('Invalid state parameter');
    }
    
    // Vérifier les erreurs
    if ($error = $request->get('error')) {
        throw new AuthenticationFailedException($error);
    }
    
    $code = $request->get('code');
    if (!$code) {
        throw new AuthenticationFailedException('Authorization code missing');
    }
    
    // Échanger le code contre un token
    $tokens = $this->exchangeCodeForToken($code);
    
    // Valider le token
    $this->validateIdToken($tokens['id_token']);
    
    // Récupérer les infos utilisateur
    $userInfo = $this->getUserInfo($tokens['access_token']);
    
    // Créer ou connecter l'utilisateur
    return $this->createOrUpdateUser($userInfo);
}

private function exchangeCodeForToken(string $code): array
{
    $codeVerifier = session()->get('itsme.code_verifier');
    
    $response = Http::asForm()->post($this->getTokenEndpoint(), [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => config('itsme.redirect'),
        'client_id' => config('itsme.client_id'),
        'client_secret' => config('itsme.client_secret'),
        'code_verifier' => $codeVerifier,
    ]);
    
    if (!$response->successful()) {
        throw new AuthenticationFailedException('Token exchange failed');
    }
    
    return $response->json();
}
```

---

### 3. Validation des Tokens JWT

#### Structure d'un ID Token
```json
{
  "iss": "https://idp.itsme.be",
  "sub": "user_unique_id",
  "aud": "your_client_id",
  "exp": 1234567890,
  "iat": 1234567890,
  "nonce": "random_nonce_value",
  "auth_time": 1234567890,
  "email": "user@example.com",
  "email_verified": true,
  "given_name": "John",
  "family_name": "Doe"
}
```

#### Validation Complète
```php
class TokenValidator
{
    public function validateIdToken(string $idToken, string $nonce): array
    {
        // Décoder le token
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new InvalidTokenException('Invalid token format');
        }
        
        $payload = json_decode(base64_decode($parts[1]), true);
        
        // Vérifier l'expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new InvalidTokenException('Token expired');
        }
        
        // Vérifier l'issuer
        $expectedIssuer = config('itsme.issuer');
        if ($payload['iss'] !== $expectedIssuer) {
            throw new InvalidTokenException('Invalid issuer');
        }
        
        // Vérifier l'audience
        if ($payload['aud'] !== config('itsme.client_id')) {
            throw new InvalidTokenException('Invalid audience');
        }
        
        // Vérifier le nonce
        if ($payload['nonce'] !== $nonce) {
            throw new InvalidTokenException('Invalid nonce');
        }
        
        // Vérifier la signature (si activé)
        if (config('itsme.verify_token_signature')) {
            $this->verifySignature($idToken);
        }
        
        return $payload;
    }
    
    private function verifySignature(string $idToken): void
    {
        // Récupérer les clés publiques JWKS
        $jwks = $this->getJwks();
        
        // Décoder le header pour obtenir le kid
        $header = json_decode(base64_decode(explode('.', $idToken)[0]), true);
        $kid = $header['kid'] ?? null;
        
        if (!$kid || !isset($jwks['keys'][$kid])) {
            throw new InvalidTokenException('Key ID not found');
        }
        
        $publicKey = $this->convertJwkToPem($jwks['keys'][$kid]);
        
        // Vérifier la signature
        if (!openssl_verify(
            explode('.', $idToken)[0] . '.' . explode('.', $idToken)[1],
            base64_decode(explode('.', $idToken)[2]),
            $publicKey,
            OPENSSL_ALGO_SHA256
        )) {
            throw new InvalidTokenException('Invalid token signature');
        }
    }
}
```

---

### 4. Récupération des Informations Utilisateur

#### Appel UserInfo Endpoint
```php
public function getUserInfo(string $accessToken): array
{
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Accept' => 'application/json',
    ])->get($this->getUserInfoEndpoint());
    
    if (!$response->successful()) {
        throw new AuthenticationFailedException('Failed to retrieve user info');
    }
    
    return $response->json();
}
```

#### Réponse Type UserInfo
```json
{
  "sub": "user_unique_id",
  "email": "user@example.com",
  "email_verified": true,
  "given_name": "John",
  "family_name": "Doe",
  "name": "John Doe",
  "phone_number": "+32470123456",
  "phone_number_verified": true
}
```

---

### 5. Création/Connexion Utilisateur Laravel

```php
protected function createOrUpdateUser(array $userInfo): User
{
    // Rechercher par itsme_id ou email
    $user = User::where('itsme_id', $userInfo['sub'])
        ->orWhere('email', $userInfo['email'])
        ->first();
    
    if ($user) {
        // Mettre à jour les informations
        $user->update([
            'itsme_id' => $userInfo['sub'],
            'email' => $userInfo['email'] ?? $user->email,
            'first_name' => $userInfo['given_name'] ?? $user->first_name,
            'last_name' => $userInfo['family_name'] ?? $user->last_name,
            'name' => $userInfo['name'] ?? $user->name,
            'phone' => $userInfo['phone_number'] ?? $user->phone,
        ]);
    } else {
        // Créer un nouvel utilisateur
        $user = User::create([
            'itsme_id' => $userInfo['sub'],
            'email' => $userInfo['email'],
            'first_name' => $userInfo['given_name'] ?? null,
            'last_name' => $userInfo['family_name'] ?? null,
            'name' => $userInfo['name'] ?? ($userInfo['given_name'] . ' ' . $userInfo['family_name']),
            'phone' => $userInfo['phone_number'] ?? null,
            'email_verified_at' => $userInfo['email_verified'] ? now() : null,
            'password' => null, // Pas de mot de passe pour les utilisateurs Itsme
        ]);
    }
    
    // Connecter l'utilisateur
    Auth::login($user);
    
    return $user;
}
```

---

### 6. PKCE (Proof Key for Code Exchange)

#### Génération Code Verifier et Challenge
```php
private function generateCodeVerifier(): string
{
    return bin2hex(random_bytes(32));
}

private function generateCodeChallenge(string $verifier): string
{
    return base64_url_encode(hash('sha256', $verifier, true));
}

private function base64_url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
```

---

### 7. Gestion des Erreurs Itsme

#### Codes d'Erreur Communs
```php
const ERROR_CODES = [
    'access_denied' => 'L\'utilisateur a refusé l\'autorisation',
    'invalid_request' => 'La requête est invalide',
    'invalid_client' => 'Client ID ou secret invalide',
    'invalid_grant' => 'Le code d\'autorisation est invalide ou expiré',
    'unauthorized_client' => 'Le client n\'est pas autorisé',
    'unsupported_response_type' => 'Type de réponse non supporté',
    'invalid_scope' => 'Scope invalide',
    'server_error' => 'Erreur serveur Itsme',
    'temporarily_unavailable' => 'Service temporairement indisponible',
];
```

#### Handler d'Erreurs
```php
public function handleError(string $error, string $errorDescription = null): void
{
    $message = self::ERROR_CODES[$error] ?? 'Une erreur est survenue';
    
    if ($errorDescription) {
        $message .= ': ' . $errorDescription;
    }
    
    Log::error('Itsme authentication error', [
        'error' => $error,
        'description' => $errorDescription,
    ]);
    
    throw new AuthenticationFailedException($message);
}
```

---

### 8. Cache de la Configuration OpenID

```php
class OpenIdDiscovery
{
    public function getConfiguration(): array
    {
        return Cache::remember('itsme.openid_config', 3600, function () {
            $discoveryUrl = config('itsme.discovery_url');
            return $this->discover($discoveryUrl);
        });
    }
    
    public function clearCache(): void
    {
        Cache::forget('itsme.openid_config');
    }
}
```

---

### 9. Exemple d'Utilisation dans une Vue Blade

```blade
{{-- resources/views/auth/login.blade.php --}}
<div class="itsme-auth-section">
    <h3>Ou connectez-vous avec</h3>
    
    @include('itsme::itsme-button', [
        'text' => 'Se connecter avec itsme®',
        'class' => 'btn-itsme-primary'
    ])
</div>
```

```blade
{{-- resources/views/itsme/itsme-button.blade.php --}}
<a href="{{ route('itsme.redirect') }}" 
   class="itsme-button {{ $class ?? '' }}"
   aria-label="{{ $text ?? 'Se connecter avec itsme' }}">
    <img src="{{ asset('vendor/itsme/logo.svg') }}" alt="itsme" class="itsme-logo">
    <span>{{ $text ?? 'Se connecter avec itsme®' }}</span>
</a>
```

---

### 10. Configuration Avancée

#### Support Multi-Environnements
```php
// config/itsme.php
return [
    'environments' => [
        'sandbox' => [
            'discovery_url' => 'https://idp.sandbox.itsme.be/.well-known/openid-configuration',
            'issuer' => 'https://idp.sandbox.itsme.be',
        ],
        'production' => [
            'discovery_url' => 'https://idp.itsme.be/.well-known/openid-configuration',
            'issuer' => 'https://idp.itsme.be',
        ],
    ],
    
    'environment' => env('ITSME_ENVIRONMENT', 'sandbox'),
];
```

#### Events Laravel
```php
// Émettre des événements pour permettre aux utilisateurs d'écouter
event(new ItsmeUserAuthenticated($user, $userInfo));
event(new ItsmeUserCreated($user, $userInfo));
event(new ItsmeAuthenticationFailed($error));
```

---

### 11. Tests Unitaires Exemples

```php
class ItsmeServiceTest extends TestCase
{
    public function test_generates_authorization_url()
    {
        $service = app(ItsmeService::class);
        
        $url = $service->getAuthorizationUrl();
        
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('client_id=', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('code_challenge=', $url);
    }
    
    public function test_validates_state_parameter()
    {
        $service = app(ItsmeService::class);
        
        session()->put('itsme.state', 'valid_state');
        
        $this->expectException(InvalidStateException::class);
        
        $service->handleCallback(new Request(['state' => 'invalid_state', 'code' => 'test_code']));
    }
    
    public function test_creates_user_on_first_login()
    {
        Http::fake([
            '*/token' => Http::response(['access_token' => 'token', 'id_token' => 'id_token']),
            '*/userinfo' => Http::response([
                'sub' => 'user123',
                'email' => 'test@example.com',
                'given_name' => 'John',
                'family_name' => 'Doe',
            ]),
        ]);
        
        $service = app(ItsmeService::class);
        
        $user = $service->handleCallback(new Request([
            'state' => session()->get('itsme.state'),
            'code' => 'test_code',
        ]));
        
        $this->assertDatabaseHas('users', [
            'itsme_id' => 'user123',
            'email' => 'test@example.com',
        ]);
    }
}
```

---

### 12. Sécurité - Checklist

- [x] Validation du state parameter (CSRF protection)
- [x] Validation du nonce (replay attack protection)
- [x] Vérification de la signature du token (si activé)
- [x] Vérification de l'expiration du token
- [x] Vérification de l'audience (client_id)
- [x] Vérification de l'issuer
- [x] Utilisation de PKCE pour les applications publiques
- [x] Validation des redirect URIs
- [x] Chiffrement des données sensibles en session
- [x] Rate limiting sur les routes d'authentification
- [x] Logging des tentatives d'authentification
- [x] Gestion sécurisée des secrets (pas dans le code)

---

*Ces détails techniques complètent le plan principal et fournissent des exemples concrets d'implémentation.*

