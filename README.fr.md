# Package Itsme Laravel

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/martin-lechene/itsme-laravel)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Package Laravel pour l'authentification via **Itsme** en utilisant OpenID Connect 1.0.

## 📋 Description

Ce package permet d'intégrer facilement l'authentification Itsme dans votre application Laravel 12. Itsme est une solution d'identité numérique belge qui permet aux utilisateurs de s'authentifier de manière sécurisée sans mot de passe.

## ✨ Fonctionnalités

- ✅ Authentification via OpenID Connect 1.0
- ✅ Support PKCE pour une sécurité renforcée
- ✅ Validation complète des tokens JWT
- ✅ Création automatique de compte utilisateur
- ✅ Découverte automatique de la configuration OpenID
- ✅ Bouton Itsme prêt à l'emploi
- ✅ Gestion complète des erreurs
- ✅ Compatible Laravel 12
- ✅ Support multilingue (FR/EN)

## 📦 Installation

### 1. Installation via Composer

```bash
composer require martin-lechene/itsme-laravel
```

### 2. Publier la configuration

```bash
php artisan vendor:publish --tag=itsme-config
```

### 3. Publier les migrations

```bash
php artisan vendor:publish --tag=itsme-migrations
php artisan migrate
```

### 4. Publier les vues (optionnel)

```bash
php artisan vendor:publish --tag=itsme-views
```

### 5. Publier les fichiers de langue (optionnel)

```bash
php artisan vendor:publish --tag=itsme-lang
```

## ⚙️ Configuration

### Variables d'environnement

Ajoutez ces variables dans votre fichier `.env` :

```env
ITSME_CLIENT_ID=your_client_id
ITSME_CLIENT_SECRET=your_client_secret
ITSME_REDIRECT_URI=https://your-app.com/itsme/callback
ITSME_ENVIRONMENT=sandbox
ITSME_USE_PKCE=true
ITSME_VERIFY_TOKEN=true
```

### Configuration du portail Itsme

1. Créez un compte sur le [portail développeur Itsme](https://www.itsme-id.com/en-BE/business/developer)
2. Enregistrez votre application
3. Configurez les redirect URIs autorisés
4. Obtenez votre Client ID et Client Secret
5. Testez en environnement sandbox

## 🚀 Utilisation

### Ajouter le bouton Itsme dans vos vues

Dans votre vue de connexion (`resources/views/auth/login.blade.php`) :

```blade
<div class="itsme-auth-section">
    <h3>Ou connectez-vous avec</h3>
    
    @include('itsme::itsme-button', [
        'text' => __('itsme::itsme.button_text'),
        'size' => 'default'
    ])
</div>
```

### Utilisation programmatique

```php
use ItsmeLaravel\Itsme\Facades\Itsme;

// Obtenir l'URL d'autorisation
$url = Itsme::getAuthorizationUrl();
return redirect($url);
```

### Routes disponibles

Le package enregistre automatiquement ces routes :

- `GET /itsme/redirect` - Redirige vers la page d'authentification Itsme
- `GET /itsme/callback` - Gère le callback après authentification

## 📝 Mapping des données utilisateur

Le package mappe automatiquement les données Itsme vers votre modèle User :

| Claim Itsme | Champ Laravel |
|-------------|---------------|
| `sub` | `itsme_id` |
| `email` | `email` |
| `given_name` | `first_name` |
| `family_name` | `last_name` |
| `name` | `name` |
| `phone_number` | `phone` |

## 🌐 Localisation

Le package supporte plusieurs langues (français et anglais). Toutes les chaînes visibles par l'utilisateur sont traduisibles.

Pour personnaliser les traductions, publiez les fichiers de langue :

```bash
php artisan vendor:publish --tag=itsme-lang
```

Puis éditez les fichiers dans `lang/vendor/itsme/fr/itsme.php` ou `lang/vendor/itsme/en/itsme.php`.

Le package utilisera automatiquement la locale de votre application (définie dans `config/app.php`).

## 🎨 Personnalisation

### Personnaliser le bouton

```blade
@include('itsme::itsme-button', [
    'text' => 'S\'inscrire avec itsme®',
    'size' => 'large', // 'small', 'default', 'large'
    'class' => 'custom-class'
])
```

### Personnaliser la création d'utilisateur

Vous pouvez écouter les événements Laravel pour personnaliser la création d'utilisateur :

```php
use Illuminate\Support\Facades\Event;
use ItsmeLaravel\Itsme\Events\ItsmeUserCreated;

Event::listen(ItsmeUserCreated::class, function ($event) {
    // Personnaliser la création d'utilisateur
    $user = $event->user;
    $userInfo = $event->userInfo;
});
```

## 🔒 Sécurité

Le package implémente plusieurs mesures de sécurité :

- ✅ **State parameter** : Protection contre les attaques CSRF
- ✅ **Nonce** : Protection contre les replay attacks
- ✅ **PKCE** : Protection contre l'interception du code d'autorisation
- ✅ **Validation des tokens** : Vérification de la signature, expiration, audience, issuer
- ✅ **Validation des redirect URIs** : Vérification que l'URI correspond à la configuration

## 🧪 Tests

```bash
composer test
```

## 📚 Documentation

Pour plus d'informations, consultez :

- [Plan du package](PLAN_PACKAGE_ITSME.md)
- [Détails techniques](DETAILS_TECHNIQUES.md)
- [Flux d'authentification](FLUX_AUTHENTIFICATION.md)
- [Exemples d'utilisation](USAGE_EXAMPLES.md)
- [Documentation officielle Itsme](https://www.itsme-id.com/en-BE/business/developer)

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request.

Veuillez lire [CONTRIBUTING.md](CONTRIBUTING.md) pour plus de détails sur notre code de conduite et le processus de soumission de pull requests.

## 📄 License

Ce package est sous licence [MIT](LICENSE).

## 🙏 Remerciements

- [Itsme](https://www.itsme-id.com/) pour leur service d'identité numérique
- [Laravel](https://laravel.com/) pour le framework
- [Laravel Socialite](https://laravel.com/docs/socialite) pour l'inspiration

## 📞 Support

Pour toute question ou problème, ouvrez une [issue](https://github.com/martin-lechene/itsme-laravel/issues).

---

**Note** : Ce package n'est pas officiellement supporté par Itsme. Il s'agit d'une implémentation communautaire basée sur la documentation publique d'Itsme.
