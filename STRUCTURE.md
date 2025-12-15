# Structure du Package Itsme Laravel

## 📁 Arborescence Complète

```
itsme-laravel/
│
├── config/
│   └── itsme.php                          # Configuration du package
│
├── database/
│   └── migrations/
│       └── 2024_01_01_000000_add_itsme_fields_to_users_table.php
│
├── resources/
│   ├── assets/                            # Assets CSS/JS (si nécessaire)
│   └── views/
│       ├── itsme-button.blade.php         # Composant bouton Itsme
│       └── itsme-error.blade.php          # Vue d'erreur
│
├── routes/
│   └── web.php                            # Routes du package
│
├── src/
│   ├── Controllers/
│   │   └── ItsmeController.php           # Contrôleur principal
│   │
│   ├── Exceptions/
│   │   ├── ItsmeException.php            # Exception de base
│   │   ├── AuthenticationFailedException.php
│   │   ├── InvalidStateException.php
│   │   ├── InvalidTokenException.php
│   │   └── ConfigurationException.php
│   │
│   ├── Facades/
│   │   └── Itsme.php                      # Facade Laravel
│   │
│   ├── Services/
│   │   ├── ItsmeService.php              # Service principal
│   │   ├── OpenIdDiscovery.php            # Découverte OpenID Connect
│   │   └── TokenValidator.php            # Validation des tokens JWT
│   │
│   └── ItsmeServiceProvider.php          # Service Provider Laravel
│
├── tests/
│   └── TestCase.php                       # Classe de base pour les tests
│
├── .gitignore
├── CHANGELOG.md
├── composer.json
├── LICENSE
├── phpunit.xml
├── README.md
├── USAGE_EXAMPLES.md
└── STRUCTURE.md
```

## 📦 Composants Principaux

### 1. Service Provider (`src/ItsmeServiceProvider.php`)
- Enregistre les services dans le container IoC
- Publie la configuration, migrations, vues et assets
- Charge les routes du package
- Enregistre les vues Blade

### 2. Services

#### `ItsmeService` (`src/Services/ItsmeService.php`)
- Génère l'URL d'autorisation
- Gère le callback d'authentification
- Échange le code d'autorisation contre un token
- Récupère les informations utilisateur

#### `OpenIdDiscovery` (`src/Services/OpenIdDiscovery.php`)
- Découvre la configuration OpenID Connect
- Cache la configuration pour améliorer les performances
- Fournit les endpoints nécessaires

#### `TokenValidator` (`src/Services/TokenValidator.php`)
- Valide les tokens JWT
- Vérifie la signature, expiration, audience, issuer
- Gère les clés publiques JWKS

### 3. Contrôleur (`src/Controllers/ItsmeController.php`)
- `redirect()` : Redirige vers Itsme
- `callback()` : Gère le retour d'authentification
- `createOrUpdateUser()` : Crée ou met à jour l'utilisateur

### 4. Routes (`routes/web.php`)
- `GET /itsme/redirect` : Initie l'authentification
- `GET /itsme/callback` : Gère le callback

### 5. Exceptions
- `ItsmeException` : Exception de base
- `AuthenticationFailedException` : Erreur d'authentification
- `InvalidStateException` : State invalide (CSRF)
- `InvalidTokenException` : Token invalide
- `ConfigurationException` : Erreur de configuration

### 6. Vues Blade

#### `itsme-button.blade.php`
- Composant bouton Itsme prêt à l'emploi
- Styles intégrés
- Responsive
- Tailles : small, default, large

#### `itsme-error.blade.php`
- Vue d'erreur personnalisable
- Affichage des messages d'erreur

### 7. Migration
- Ajoute le champ `itsme_id` à la table `users`
- Index unique sur `itsme_id`

## 🔧 Configuration

### Fichier de configuration (`config/itsme.php`)
- Credentials (Client ID, Secret)
- Endpoints (découverts automatiquement ou manuels)
- Options (PKCE, vérification signature)
- Environnements (sandbox, production)

### Variables d'environnement
- `ITSME_CLIENT_ID`
- `ITSME_CLIENT_SECRET`
- `ITSME_REDIRECT_URI`
- `ITSME_ENVIRONMENT`
- `ITSME_USE_PKCE`
- `ITSME_VERIFY_TOKEN`

## 🚀 Utilisation

### Installation
```bash
composer require martin-lechene/itsme-laravel
php artisan vendor:publish --tag=itsme-config
php artisan vendor:publish --tag=itsme-migrations
php artisan migrate
```

### Dans une vue Blade
```blade
@include('itsme::itsme-button')
```

### Programmatique
```php
use ItsmeLaravel\Itsme\Facades\Itsme;

$url = Itsme::getAuthorizationUrl();
```

## 📝 Fichiers de Documentation

- `README.md` : Documentation principale
- `PLAN_PACKAGE_ITSME.md` : Plan de développement détaillé
- `DETAILS_TECHNIQUES.md` : Détails techniques et exemples de code
- `FLUX_AUTHENTIFICATION.md` : Diagramme de flux d'authentification
- `USAGE_EXAMPLES.md` : Exemples d'utilisation
- `STRUCTURE.md` : Ce fichier

## 🔒 Sécurité

Le package implémente :
- ✅ State parameter (CSRF protection)
- ✅ Nonce (replay attack protection)
- ✅ PKCE (code interception protection)
- ✅ Validation complète des tokens JWT
- ✅ Vérification de signature
- ✅ Validation expiration, audience, issuer

## 📊 Flux d'Authentification

1. Utilisateur clique sur bouton → `/itsme/redirect`
2. Génération state, nonce, PKCE
3. Redirection vers Itsme
4. Authentification utilisateur
5. Callback → `/itsme/callback`
6. Vérification state
7. Échange code contre token
8. Validation token
9. Récupération UserInfo
10. Création/connexion utilisateur
11. Redirection

## 🧪 Tests

Structure de tests :
- `tests/TestCase.php` : Classe de base
- Tests unitaires : Services, validation
- Tests feature : Flux d'authentification complet

## 📦 Dépendances

### Requises
- `laravel/framework` ^12.0
- `laravel/socialite` ^5.0
- `guzzlehttp/guzzle` ^7.0
- `firebase/php-jwt` ^6.0

### Développement
- `orchestra/testbench` ^10.0
- `phpunit/phpunit` ^11.0
- `mockery/mockery` ^1.6

---

*Structure créée le 2024-01-01*

