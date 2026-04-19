# itsme® Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/martin-lechene/itsme-laravel.svg?style=flat-square)](https://packagist.org/packages/martin-lechene/itsme-laravel)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg?style=flat-square)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12%20%7C%2013-red.svg?style=flat-square)](https://laravel.com)
[![Tests](https://img.shields.io/github/actions/workflow/status/martin-lechene/itsme-laravel/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/martin-lechene/itsme-laravel/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

Package Laravel pour l'authentification via **itsme®** — la solution d'identité numérique belge — en utilisant le protocole **OpenID Connect 1.0**.

> **Note :** Ce package est communautaire et n'est pas officiellement maintenu par itsme®. Il est basé sur la documentation publique OpenID Connect d'itsme®.

---

## Table des matières

- [Fonctionnalités](#-fonctionnalités)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Utilisation](#-utilisation)
  - [Ajouter le bouton itsme®](#ajouter-le-bouton-itsme)
  - [Routes](#routes)
  - [Facade](#facade)
  - [Middleware](#middleware)
  - [Événements](#événements)
  - [Commande Artisan](#commande-artisan)
- [Mapping des données utilisateur](#-mapping-des-données-utilisateur)
- [Personnalisation](#-personnalisation)
  - [Personnaliser le bouton](#personnaliser-le-bouton)
  - [Surcharger les vues](#surcharger-les-vues)
  - [Surcharger les traductions](#surcharger-les-traductions)
  - [Personnaliser la création d'utilisateur](#personnaliser-la-création-dutilisateur)
- [Sécurité](#-sécurité)
- [Tests](#-tests)
- [Guide de mise à jour](#-guide-de-mise-à-jour)
- [Contribution](#-contribution)
- [Licence](#-licence)

---

## ✨ Fonctionnalités

| Fonctionnalité | Détails |
|---|---|
| OpenID Connect 1.0 | Flux d'autorisation de code OIDC complet |
| PKCE | Proof Key for Code Exchange (activé par défaut) |
| Validation JWT | Signature, expiration, audience, issuer, nonce |
| JWKS | Récupération automatique des clés publiques depuis l'endpoint JWKS |
| Auto-discovery | Configuration OpenID découverte depuis le well-known endpoint |
| Gestion utilisateurs | Création ou mise à jour via les claims itsme® à chaque connexion |
| Événements | `ItsmeUserCreated`, `ItsmeUserAuthenticated`, `ItsmeAuthenticationFailed` |
| Middleware | `itsme.auth` — protège les routes nécessitant une auth itsme® |
| Composants Blade | Bouton itsme® prêt à l'emploi (3 tailles, personnalisable) |
| i18n | Traductions anglais et français incluses |
| Artisan | `php artisan itsme:test-config` — diagnostiquer la configuration |
| Laravel 12 & 13 | Compatible avec les deux versions |
| PHP 8.2+ | Nécessite PHP 8.2, 8.3 ou 8.4 |

---

## 📋 Prérequis

| Prérequis | Version |
|---|---|
| PHP | `^8.2` |
| Laravel | `^12.0` ou `^13.0` |
| GuzzleHTTP | `^7.0` |
| firebase/php-jwt | `^7.0` |

---

## 📦 Installation

### Étape 1 — Installation via Composer

```bash
composer require martin-lechene/itsme-laravel
```

Le service provider et la facade `Itsme` sont enregistrés automatiquement via le package auto-discovery de Laravel.

### Étape 2 — Publier la configuration

```bash
php artisan vendor:publish --tag=itsme-config
```

Crée `config/itsme.php`.

### Étape 3 — Publier et exécuter la migration

```bash
php artisan vendor:publish --tag=itsme-migrations
php artisan migrate
```

Ajoute une colonne `itsme_id` à votre table `users`.

### Étape 4 (optionnel) — Publier les vues

```bash
php artisan vendor:publish --tag=itsme-views
```

Publie les vues Blade dans `resources/views/vendor/itsme/`.

### Étape 5 (optionnel) — Publier les fichiers de langue

```bash
php artisan vendor:publish --tag=itsme-lang
```

Publie les traductions dans `lang/vendor/itsme/`.

---

## ⚙️ Configuration

### Variables d'environnement

Ajoutez les variables suivantes dans votre fichier `.env` :

```dotenv
# ── Requis ────────────────────────────────────────────────────────────────────
ITSME_CLIENT_ID=votre_client_id
ITSME_CLIENT_SECRET=votre_client_secret
ITSME_REDIRECT_URI=https://votre-app.com/itsme/callback

# ── Environnement ─────────────────────────────────────────────────────────────
# 'sandbox' (défaut) pour le développement, 'production' pour la mise en ligne
ITSME_ENVIRONMENT=sandbox

# ── Sécurité (recommandé : laisser les valeurs par défaut en production) ──────
ITSME_USE_PKCE=true
ITSME_VERIFY_TOKEN=true
```

### Référence config/itsme.php

```php
return [
    // Credentials OAuth 2.0
    'client_id'     => env('ITSME_CLIENT_ID'),
    'client_secret' => env('ITSME_CLIENT_SECRET'),
    'redirect'      => env('ITSME_REDIRECT_URI', '/itsme/callback'),

    // Environnement : 'sandbox' ou 'production'
    'environment'   => env('ITSME_ENVIRONMENT', 'sandbox'),

    // Scopes OpenID à demander
    'scopes'        => ['openid', 'profile', 'email', 'phone'],

    // PKCE (recommandé)
    'use_pkce'                => env('ITSME_USE_PKCE', true),

    // Vérification de signature JWT (toujours true en production)
    'verify_token_signature'  => env('ITSME_VERIFY_TOKEN', true),

    // Surcharge discovery/issuer par environnement (auto-configuré par défaut)
    'environments' => [
        'sandbox' => [
            'discovery_url' => 'https://idp.sandbox.itsme.be/.well-known/openid-configuration',
            'issuer'        => 'https://idp.sandbox.itsme.be',
        ],
        'production' => [
            'discovery_url' => 'https://idp.itsme.be/.well-known/openid-configuration',
            'issuer'        => 'https://idp.itsme.be',
        ],
    ],
];
```

### Configuration du portail itsme®

1. Inscrivez-vous sur le [portail développeur itsme®](https://www.itsme-id.com/en-BE/business/developer)
2. Créez une application et notez votre **Client ID** et **Client Secret**
3. Enregistrez votre URI de redirection (ex. `https://votre-app.com/itsme/callback`)
4. Testez avec l'environnement **sandbox** avant la mise en production

---

## 🚀 Utilisation

### Ajouter le bouton itsme®

Incluez le partial Blade n'importe où dans votre vue de connexion :

```blade
{{-- resources/views/auth/login.blade.php --}}

@include('itsme::itsme-button')
```

Ou avec des options personnalisées :

```blade
@include('itsme::itsme-button', [
    'text'  => __('itsme::itsme.button_text'),
    'size'  => 'large',   // 'small' | 'default' | 'large'
    'class' => 'mt-4 w-full',
])
```

### Routes

Le package enregistre automatiquement deux routes dans le groupe middleware `web` :

| Méthode | URI | Nom | Description |
|---------|-----|-----|-------------|
| `GET` | `/itsme/redirect` | `itsme.redirect` | Construit l'URL d'autorisation et redirige l'utilisateur vers itsme® |
| `GET` | `/itsme/callback` | `itsme.callback` | Traite le callback itsme®, crée/met à jour l'utilisateur et le connecte |

Lien direct vers la route de redirection :

```blade
<a href="{{ route('itsme.redirect') }}">Se connecter avec itsme®</a>
```

### Facade

Utilisez la facade `Itsme` pour un contrôle programmatique :

```php
use ItsmeLaravel\Itsme\Facades\Itsme;

// Construire l'URL d'autorisation (stocke state/nonce en session)
$url = Itsme::getAuthorizationUrl();
return redirect($url);

// Traiter le callback et obtenir les claims ID token + UserInfo fusionnés
$userInfo = Itsme::handleCallback($request);
// $userInfo['sub'], $userInfo['email'], $userInfo['given_name'], …
```

### Middleware

Le package enregistre un alias de middleware `itsme.auth` qui protège les routes nécessitant une authentification via itsme®. Un utilisateur connecté via une autre méthode sera déconnecté et redirigé.

**Protection au niveau des routes :**

```php
// routes/web.php
Route::middleware(['auth', 'itsme.auth'])->group(function () {
    Route::get('/tableau-de-bord', DashboardController::class);
});
```

**Protection au niveau du contrôleur :**

```php
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'itsme.auth']);
    }
}
```

### Événements

Écoutez les événements itsme® dans votre application (ex. dans `EventServiceProvider` ou via `#[AsEventListener]`) :

#### `ItsmeUserCreated`

Déclenché lors de la création d'un **nouvel** utilisateur via itsme®.

```php
use ItsmeLaravel\Itsme\Events\ItsmeUserCreated;

Event::listen(ItsmeUserCreated::class, function (ItsmeUserCreated $event) {
    $event->user;     // Instance du modèle Eloquent
    $event->userInfo; // Tableau brut des claims OIDC d'itsme®
    
    // ex. envoyer un email de bienvenue
    Mail::to($event->user)->send(new WelcomeMail());
});
```

#### `ItsmeUserAuthenticated`

Déclenché à **chaque** connexion réussie (nouveaux et anciens utilisateurs).

```php
use ItsmeLaravel\Itsme\Events\ItsmeUserAuthenticated;

Event::listen(ItsmeUserAuthenticated::class, function (ItsmeUserAuthenticated $event) {
    $event->user;     // Instance du modèle Eloquent
    $event->userInfo; // Tableau brut des claims OIDC

    // ex. enregistrer la connexion
    \Log::info('Connexion itsme', ['user_id' => $event->user->id]);
});
```

#### `ItsmeAuthenticationFailed`

Déclenché en cas d'échec de l'authentification (token invalide, accès refusé, etc.).

```php
use ItsmeLaravel\Itsme\Events\ItsmeAuthenticationFailed;

Event::listen(ItsmeAuthenticationFailed::class, function (ItsmeAuthenticationFailed $event) {
    $event->error;            // string : code d'erreur ou message
    $event->errorDescription; // string|null : description lisible
});
```

### Commande Artisan

Diagnostiquer votre configuration itsme® et la connectivité réseau :

```bash
php artisan itsme:test-config
```

Exemple de sortie :

```
Testing Itsme Configuration...

Checking configuration...
  ✅ Client ID: votre_client_id
  ✅ Client Secret: ***
  ✅ Redirect URI: https://votre-app.com/itsme/callback
  ✅ Environment: sandbox
  ✅ Use PKCE: Yes
  ✅ Verify Token: Yes

Testing OpenID Connect discovery...
  ✅ Discovery successful
  ✅ Authorization endpoint: https://idp.sandbox.itsme.be/...
  ✅ Token endpoint: https://idp.sandbox.itsme.be/...
  ✅ UserInfo endpoint: https://idp.sandbox.itsme.be/...

✅ Configuration test completed!
```

---

## 📋 Mapping des données utilisateur

Le package mappe automatiquement les claims OIDC d'itsme® vers votre modèle User à chaque connexion :

| Claim itsme® | Champ modèle User | Notes |
|---|---|---|
| `sub` | `itsme_id` | Identifiant unique — toujours présent |
| `email` | `email` | Peut être absent si le scope n'est pas demandé |
| `email_verified` | `email_verified_at` | Défini à `now()` si `true` |
| `given_name` | `first_name` | Fait partie du scope `profile` |
| `family_name` | `last_name` | Fait partie du scope `profile` |
| `name` | `name` | Nom complet ; composé de given+family si absent |
| `phone_number` | `phone` | Fait partie du scope `phone` |

> Le package ignore silencieusement les champs qui n'existent pas sur votre modèle User.

---

## 🎨 Personnalisation

### Personnaliser le bouton

```blade
@include('itsme::itsme-button', [
    'text'  => 'Se connecter avec itsme®',
    'size'  => 'large',           // 'small' | 'default' | 'large'
    'class' => 'ma-classe-custom',
])
```

Référence des tailles :

| Taille | Padding | Taille de police |
|--------|---------|------------------|
| `small` | `8px 16px` | `14px` |
| `default` | `12px 24px` | `16px` |
| `large` | `16px 32px` | `18px` |

### Surcharger les vues

Après publication des vues, éditez-les dans `resources/views/vendor/itsme/` :

```
resources/views/vendor/itsme/
├── itsme-button.blade.php   ← Le bouton itsme®
└── itsme-error.blade.php    ← Composant d'affichage d'erreur
```

### Surcharger les traductions

Après publication des fichiers de langue, éditez-les dans `lang/vendor/itsme/` :

```
lang/vendor/itsme/
├── en/itsme.php
└── fr/itsme.php
```

Le package utilise automatiquement la locale de votre application (`config/app.php` → `locale`).

### Personnaliser la création d'utilisateur

Écoutez `ItsmeUserCreated` pour exécuter une logique custom après la création d'un utilisateur (rôles, notifications, etc.) :

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \ItsmeLaravel\Itsme\Events\ItsmeUserCreated::class => [
        \App\Listeners\AssignerRoleParDefaut::class,
        \App\Listeners\EnvoyerEmailBienvenue::class,
    ],
    \ItsmeLaravel\Itsme\Events\ItsmeUserAuthenticated::class => [
        \App\Listeners\MettreAJourDerniereConnexion::class,
    ],
];
```

Pour changer **comment** les utilisateurs sont créés/mis à jour, étendez `ItsmeController` et surchargez `createOrUpdateUser()` :

```php
// app/Http/Controllers/MonItsmeController.php
use ItsmeLaravel\Itsme\Controllers\ItsmeController;

class MonItsmeController extends ItsmeController
{
    protected function createOrUpdateUser(array $userInfo)
    {
        return \App\Models\User::updateOrCreate(
            ['itsme_id' => $userInfo['sub']],
            [
                'name'  => $userInfo['name'] ?? 'Inconnu',
                'email' => $userInfo['email'] ?? null,
            ]
        );
    }
}
```

```php
// routes/web.php
use App\Http\Controllers\MonItsmeController;

Route::get('/itsme/callback', [MonItsmeController::class, 'callback'])
     ->name('itsme.callback');
```

---

## 🔒 Sécurité

Le package implémente toutes les mesures de sécurité OpenID Connect recommandées :

| Mesure | Description |
|---|---|
| **State** | Valeur aléatoire par requête stockée en session et vérifiée dans le callback pour prévenir les attaques CSRF |
| **Nonce** | Valeur aléatoire par requête intégrée dans le claim ID token et vérifiée à la réception pour prévenir les replay attacks |
| **PKCE** | Proof Key for Code Exchange (méthode S256) prévient l'interception des codes d'autorisation |
| **Signature JWT** | La signature du token ID est vérifiée à l'aide des clés publiques du endpoint JWKS d'itsme® |
| **Expiration du token** | Le claim `exp` est vérifié par rapport à l'heure actuelle |
| **Audience** | Le claim `aud` est vérifié par rapport à votre `client_id` configuré |
| **Issuer** | Le claim `iss` est vérifié par rapport à l'issuer attendu pour votre environnement |

> **Checklist production**
> - Définir `ITSME_ENVIRONMENT=production`
> - Définir `ITSME_USE_PKCE=true` (défaut)
> - Définir `ITSME_VERIFY_TOKEN=true` (défaut)
> - S'assurer que `ITSME_REDIRECT_URI` utilise `https://`
> - N'enregistrer que votre URI de redirection de production dans le portail itsme®

---

## 🧪 Tests

### Exécuter la suite de tests

```bash
composer test
```

### Exécuter avec couverture

```bash
composer test-coverage
# Rapport HTML généré dans ./coverage/
```

### Analyse statique

```bash
composer analyse
```

### Vérification du style de code

```bash
composer format -- --dry-run --diff
```

### Dans votre propre application

Utilisez `Http::fake()` de Laravel pour mocker les réponses itsme® dans vos tests de fonctionnalités :

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*/token'    => Http::response([
        'access_token' => 'fake_access_token',
        'id_token'     => $this->buildFakeIdToken(),
        'token_type'   => 'Bearer',
    ]),
    '*/userinfo' => Http::response([
        'sub'         => 'user-sub-123',
        'email'       => 'user@example.com',
        'given_name'  => 'Jeanne',
        'family_name' => 'Dupont',
    ]),
]);

$response = $this->get('/itsme/callback?code=fake_code&state=' . session('itsme.state'));
$response->assertRedirect('/');
```

---

## 🔄 Guide de mise à jour

### De 1.0.x vers 1.1.x

1. Exécutez `composer update martin-lechene/itsme-laravel`
2. **Alias middleware enregistré** : L'alias middleware `itsme.auth` est désormais enregistré automatiquement. Si vous l'avez précédemment enregistré manuellement, supprimez le doublon.
3. **Clé de traduction ajoutée** : `itsme.errors.itsme_auth_required` a été ajoutée aux deux fichiers `en` et `fr`. Si vous avez publié les fichiers de langue, ajoutez cette clé manuellement :
   - **EN** : `'itsme_auth_required' => 'This page requires authentication via Itsme.'`
   - **FR** : `'itsme_auth_required' => 'Cette page nécessite une authentification via Itsme.'`
4. **`laravel/socialite` supprimé** des dépendances — aucune action nécessaire.

---

## 🗂 Structure du projet

```
martin-lechene/itsme-laravel/
├── config/
│   └── itsme.php                         ← Configuration du package
├── database/migrations/
│   └── 2024_01_01_…_add_itsme_fields.php ← Ajoute itsme_id à la table users
├── resources/
│   ├── lang/
│   │   ├── en/itsme.php                  ← Traductions anglaises
│   │   └── fr/itsme.php                  ← Traductions françaises
│   └── views/
│       ├── itsme-button.blade.php        ← Composant bouton itsme®
│       └── itsme-error.blade.php         ← Composant d'affichage d'erreur
├── routes/
│   └── web.php                           ← /itsme/redirect + /itsme/callback
└── src/
    ├── Console/Commands/
    │   └── TestItsmeConfig.php           ← php artisan itsme:test-config
    ├── Controllers/
    │   └── ItsmeController.php           ← Gère redirect + callback
    ├── Events/
    │   ├── ItsmeAuthenticationFailed.php
    │   ├── ItsmeUserAuthenticated.php
    │   └── ItsmeUserCreated.php
    ├── Exceptions/
    │   ├── AuthenticationFailedException.php
    │   ├── ConfigurationException.php
    │   ├── InvalidStateException.php
    │   ├── InvalidTokenException.php
    │   └── ItsmeException.php
    ├── Facades/
    │   └── Itsme.php                     ← Facade Itsme
    ├── Middleware/
    │   └── RequireItsmeAuth.php          ← Middleware itsme.auth
    ├── Services/
    │   ├── ItsmeService.php              ← Flux OIDC principal
    │   ├── OpenIdDiscovery.php           ← Discovery + cache
    │   └── TokenValidator.php            ← Validation JWT
    └── ItsmeServiceProvider.php
```

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Veuillez suivre ces étapes :

1. Forkez le repository
2. Créez une branche : `git checkout -b feature/ma-fonctionnalite`
3. Écrivez des tests pour vos modifications
4. Vérifiez que tous les tests passent : `composer test`
5. Vérifiez le style de code : `composer format -- --dry-run --diff`
6. Ouvrez une Pull Request

Veuillez lire [CONTRIBUTING.md](CONTRIBUTING.md) pour plus de détails.

---

## 📄 Licence

Ce package est un logiciel open-source sous licence [MIT](LICENSE).

---

## 🙏 Remerciements

- [itsme®](https://www.itsme-id.com/) pour leur service d'identité numérique belge
- [Laravel](https://laravel.com/) pour le framework
- [firebase/php-jwt](https://github.com/firebase/php-jwt) pour la gestion des JWT
