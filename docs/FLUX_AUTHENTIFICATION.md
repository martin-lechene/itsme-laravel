# Flux d'Authentification Itsme - Diagramme et Explications

## 🔄 Diagramme de Flux Complet

```
┌─────────────┐
│   Utilisateur   │
└──────┬─────────┘
       │
       │ 1. Clique sur "Se connecter avec itsme®"
       ▼
┌─────────────────────────────────────┐
│   Application Laravel               │
│   Route: /itsme/redirect           │
│   Controller: ItsmeController      │
└──────┬──────────────────────────────┘
       │
       │ 2. Génère state, nonce, PKCE
       │    Stocke en session
       │
       ▼
┌─────────────────────────────────────┐
│   Génération URL d'Autorisation      │
│   - state (CSRF protection)         │
│   - nonce (replay protection)       │
│   - code_challenge (PKCE)           │
│   - scopes (openid, profile, email)  │
└──────┬──────────────────────────────┘
       │
       │ 3. Redirection HTTP 302
       │
       ▼
┌─────────────────────────────────────┐
│   Itsme Authorization Endpoint      │
│   https://idp.itsme.be/authorize    │
└──────┬──────────────────────────────┘
       │
       │ 4. Affiche la page de connexion
       │
       ▼
┌─────────────────────────────────────┐
│   Utilisateur s'authentifie          │
│   - Code PIN / Biométrie            │
│   - Confirmation dans l'app mobile   │
└──────┬──────────────────────────────┘
       │
       │ 5. Authentification réussie
       │
       ▼
┌─────────────────────────────────────┐
│   Itsme génère authorization code   │
│   Redirection vers callback URL     │
│   ?code=AUTHORIZATION_CODE          │
│   &state=STATE_VALUE                 │
└──────┬──────────────────────────────┘
       │
       │ 6. HTTP GET vers callback
       │
       ▼
┌─────────────────────────────────────┐
│   Application Laravel                │
│   Route: /itsme/callback            │
│   Controller: ItsmeController       │
└──────┬──────────────────────────────┘
       │
       │ 7. Vérifie le state
       │    Compare avec session
       │
       ▼
┌─────────────────────────────────────┐
│   Validation State Parameter        │
│   ✓ State correspond               │
│   ✓ Pas d'erreur dans la requête   │
└──────┬──────────────────────────────┘
       │
       │ 8. Échange code contre token
       │
       ▼
┌─────────────────────────────────────┐
│   Itsme Token Endpoint              │
│   POST /token                        │
│   Body:                              │
│   - grant_type: authorization_code  │
│   - code: AUTHORIZATION_CODE        │
│   - code_verifier: PKCE_VERIFIER    │
│   - client_id, client_secret        │
└──────┬──────────────────────────────┘
       │
       │ 9. Réponse avec tokens
       │
       ▼
┌─────────────────────────────────────┐
│   Réception Tokens                  │
│   {                                 │
│     "access_token": "...",          │
│     "id_token": "JWT_TOKEN",        │
│     "token_type": "Bearer",         │
│     "expires_in": 3600              │
│   }                                 │
└──────┬──────────────────────────────┘
       │
       │ 10. Validation ID Token
       │
       ▼
┌─────────────────────────────────────┐
│   Validation JWT ID Token           │
│   ✓ Signature (si activé)          │
│   ✓ Expiration                     │
│   ✓ Issuer                         │
│   ✓ Audience (client_id)           │
│   ✓ Nonce                          │
└──────┬──────────────────────────────┘
       │
       │ 11. Récupération UserInfo
       │
       ▼
┌─────────────────────────────────────┐
│   Itsme UserInfo Endpoint           │
│   GET /userinfo                     │
│   Header: Authorization: Bearer ... │
└──────┬──────────────────────────────┘
       │
       │ 12. Réponse avec infos utilisateur
       │
       ▼
┌─────────────────────────────────────┐
│   Données Utilisateur               │
│   {                                 │
│     "sub": "user_unique_id",        │
│     "email": "user@example.com",    │
│     "given_name": "John",           │
│     "family_name": "Doe",           │
│     "phone_number": "+32470123456"   │
│   }                                 │
└──────┬──────────────────────────────┘
       │
       │ 13. Recherche/Création utilisateur
       │
       ▼
┌─────────────────────────────────────┐
│   Gestion Utilisateur Laravel       │
│   - Recherche par itsme_id ou email │
│   - Création si n'existe pas        │
│   - Mise à jour si existe           │
└──────┬──────────────────────────────┘
       │
       │ 14. Connexion utilisateur
       │
       ▼
┌─────────────────────────────────────┐
│   Auth::login($user)                │
│   Session Laravel créée             │
└──────┬──────────────────────────────┘
       │
       │ 15. Redirection
       │
       ▼
┌─────────────────────────────────────┐
│   Page d'accueil / Dashboard        │
│   Utilisateur connecté              │
└─────────────────────────────────────┘
```

---

## 📋 Étapes Détaillées avec Code

### Étape 1 : Initialisation de l'Authentification

**Route :**
```php
Route::get('/itsme/redirect', [ItsmeController::class, 'redirect'])
    ->name('itsme.redirect');
```

**Contrôleur :**
```php
public function redirect()
{
    $url = Itsme::getAuthorizationUrl();
    return redirect($url);
}
```

**Génération des paramètres de sécurité :**
```php
// State : Protection CSRF
$state = bin2hex(random_bytes(16));

// Nonce : Protection replay attack
$nonce = bin2hex(random_bytes(16));

// PKCE : Code verifier et challenge
$codeVerifier = bin2hex(random_bytes(32));
$codeChallenge = base64_url_encode(hash('sha256', $codeVerifier, true));

// Stockage en session
session()->put([
    'itsme.state' => $state,
    'itsme.nonce' => $nonce,
    'itsme.code_verifier' => $codeVerifier,
]);
```

---

### Étape 2 : Construction de l'URL d'Autorisation

```php
$params = [
    'response_type' => 'code',
    'client_id' => config('itsme.client_id'),
    'redirect_uri' => config('itsme.redirect'),
    'scope' => 'openid profile email phone',
    'state' => $state,
    'nonce' => $nonce,
    'code_challenge' => $codeChallenge,
    'code_challenge_method' => 'S256',
];

$url = config('itsme.authorization_endpoint') . '?' . http_build_query($params);
```

**Exemple d'URL générée :**
```
https://idp.itsme.be/authorize?
  response_type=code&
  client_id=your_client_id&
  redirect_uri=https://yourapp.com/itsme/callback&
  scope=openid%20profile%20email%20phone&
  state=abc123def456&
  nonce=xyz789uvw012&
  code_challenge=E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM&
  code_challenge_method=S256
```

---

### Étape 3 : Authentification sur Itsme

L'utilisateur est redirigé vers Itsme où il doit :
1. Entrer son code PIN ou utiliser la biométrie
2. Confirmer l'autorisation dans l'application mobile Itsme
3. Valider les permissions demandées

---

### Étape 4 : Callback avec Authorization Code

**URL de retour :**
```
https://yourapp.com/itsme/callback?
  code=AUTHORIZATION_CODE_12345&
  state=abc123def456
```

**Route :**
```php
Route::get('/itsme/callback', [ItsmeController::class, 'callback'])
    ->name('itsme.callback');
```

---

### Étape 5 : Validation et Échange du Token

```php
public function callback(Request $request)
{
    // Vérifier les erreurs
    if ($error = $request->get('error')) {
        return $this->handleError($error);
    }
    
    // Vérifier le state
    $state = $request->get('state');
    if ($state !== session()->get('itsme.state')) {
        throw new InvalidStateException('Invalid state');
    }
    
    // Échanger le code contre un token
    $code = $request->get('code');
    $tokens = $this->exchangeCodeForToken($code);
    
    // Valider le token
    $nonce = session()->get('itsme.nonce');
    $idTokenData = $this->validateIdToken($tokens['id_token'], $nonce);
    
    // Récupérer les infos utilisateur
    $userInfo = $this->getUserInfo($tokens['access_token']);
    
    // Créer ou connecter l'utilisateur
    $user = $this->createOrUpdateUser($userInfo);
    
    // Nettoyer la session
    session()->forget(['itsme.state', 'itsme.nonce', 'itsme.code_verifier']);
    
    // Rediriger
    return redirect()->intended('/dashboard');
}
```

---

### Étape 6 : Échange Code contre Token

**Requête POST :**
```http
POST https://idp.itsme.be/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
code=AUTHORIZATION_CODE_12345&
redirect_uri=https://yourapp.com/itsme/callback&
client_id=your_client_id&
client_secret=your_client_secret&
code_verifier=CODE_VERIFIER_FROM_SESSION
```

**Réponse :**
```json
{
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "id_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

---

### Étape 7 : Validation du ID Token

**Décodage JWT :**
```php
$parts = explode('.', $idToken);
$header = json_decode(base64_decode($parts[0]), true);
$payload = json_decode(base64_decode($parts[1]), true);
```

**Vérifications :**
1. ✅ **Expiration** : `$payload['exp'] > time()`
2. ✅ **Issuer** : `$payload['iss'] === 'https://idp.itsme.be'`
3. ✅ **Audience** : `$payload['aud'] === config('itsme.client_id')`
4. ✅ **Nonce** : `$payload['nonce'] === $nonce`
5. ✅ **Signature** : Vérification avec la clé publique JWKS

---

### Étape 8 : Récupération UserInfo

**Requête GET :**
```http
GET https://idp.itsme.be/userinfo
Authorization: Bearer ACCESS_TOKEN
Accept: application/json
```

**Réponse :**
```json
{
  "sub": "user_unique_identifier_12345",
  "email": "john.doe@example.com",
  "email_verified": true,
  "given_name": "John",
  "family_name": "Doe",
  "name": "John Doe",
  "phone_number": "+32470123456",
  "phone_number_verified": true
}
```

---

### Étape 9 : Création/Connexion Utilisateur

**Logique :**
```php
// Rechercher l'utilisateur
$user = User::where('itsme_id', $userInfo['sub'])
    ->orWhere('email', $userInfo['email'])
    ->first();

if (!$user) {
    // Créer un nouvel utilisateur
    $user = User::create([
        'itsme_id' => $userInfo['sub'],
        'email' => $userInfo['email'],
        'first_name' => $userInfo['given_name'] ?? null,
        'last_name' => $userInfo['family_name'] ?? null,
        'name' => $userInfo['name'] ?? null,
        'phone' => $userInfo['phone_number'] ?? null,
        'email_verified_at' => $userInfo['email_verified'] ? now() : null,
    ]);
} else {
    // Mettre à jour les informations
    $user->update([
        'itsme_id' => $userInfo['sub'],
        'email' => $userInfo['email'] ?? $user->email,
        'first_name' => $userInfo['given_name'] ?? $user->first_name,
        'last_name' => $userInfo['family_name'] ?? $user->last_name,
    ]);
}

// Connecter l'utilisateur
Auth::login($user);
```

---

## 🔒 Points de Sécurité dans le Flux

### 1. Protection CSRF (State Parameter)
- Généré côté serveur et stocké en session
- Inclus dans l'URL de redirection
- Vérifié au retour du callback
- Empêche les attaques CSRF

### 2. Protection Replay Attack (Nonce)
- Généré côté serveur et stocké en session
- Inclus dans le ID token
- Vérifié lors de la validation du token
- Empêche la réutilisation de tokens

### 3. PKCE (Proof Key for Code Exchange)
- Code verifier généré côté serveur
- Code challenge envoyé à Itsme
- Code verifier utilisé lors de l'échange du token
- Protège contre l'interception du code d'autorisation

### 4. Validation des Tokens
- Vérification de la signature (si activé)
- Vérification de l'expiration
- Vérification de l'audience (client_id)
- Vérification de l'issuer
- Vérification du nonce

---

## ⚠️ Gestion des Erreurs

### Erreurs Possibles

1. **Utilisateur refuse l'autorisation**
   ```
   Callback: ?error=access_denied
   ```

2. **Code d'autorisation invalide ou expiré**
   ```
   Token endpoint retourne: invalid_grant
   ```

3. **State invalide**
   ```
   Exception: InvalidStateException
   ```

4. **Token invalide**
   ```
   Exception: InvalidTokenException
   ```

### Gestion des Erreurs

```php
try {
    $user = $this->itsmeService->handleCallback($request);
    return redirect()->intended('/dashboard');
} catch (InvalidStateException $e) {
    return redirect()->route('login')
        ->with('error', 'Session expirée. Veuillez réessayer.');
} catch (AuthenticationFailedException $e) {
    return redirect()->route('login')
        ->with('error', 'Authentification échouée : ' . $e->getMessage());
} catch (InvalidTokenException $e) {
    Log::error('Invalid token', ['error' => $e->getMessage()]);
    return redirect()->route('login')
        ->with('error', 'Erreur de sécurité. Veuillez réessayer.');
}
```

---

## 📊 Séquence Temporelle

```
T+0ms    : Utilisateur clique sur le bouton
T+50ms   : Génération state, nonce, PKCE
T+100ms  : Redirection vers Itsme
T+500ms  : Page Itsme chargée
T+5s     : Utilisateur s'authentifie
T+5.5s   : Redirection vers callback
T+5.6s   : Vérification state
T+5.7s   : Échange code contre token (HTTP)
T+6s     : Réception tokens
T+6.1s   : Validation ID token
T+6.2s   : Récupération UserInfo (HTTP)
T+6.5s   : Création/connexion utilisateur
T+6.6s   : Auth::login()
T+6.7s   : Redirection vers dashboard
T+7s     : Utilisateur connecté
```

---

## 🎯 Résumé du Flux

1. **Initiation** : Utilisateur clique sur bouton → Génération paramètres de sécurité
2. **Redirection** : Redirection vers Itsme avec tous les paramètres
3. **Authentification** : Utilisateur s'authentifie sur Itsme
4. **Callback** : Retour avec code d'autorisation
5. **Validation** : Vérification du state (CSRF protection)
6. **Échange** : Code échangé contre access_token et id_token
7. **Validation Token** : Vérification de l'intégrité et validité du token
8. **UserInfo** : Récupération des informations utilisateur
9. **Création/Connexion** : Gestion de l'utilisateur dans Laravel
10. **Session** : Connexion de l'utilisateur et redirection

---

*Ce document décrit le flux complet d'authentification Itsme avec tous les détails techniques nécessaires.*

