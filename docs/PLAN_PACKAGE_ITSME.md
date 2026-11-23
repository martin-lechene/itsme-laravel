# Plan Complet - Package Laravel Itsme

## 📋 Vue d'ensemble

Ce document détaille le plan complet pour créer un package Laravel 12 permettant l'intégration d'Itsme pour l'authentification et l'inscription des utilisateurs.

---

## 🎯 Objectifs du Package

1. **Authentification via Itsme** : Permettre aux utilisateurs de se connecter avec leur compte Itsme
2. **Inscription automatique** : Créer automatiquement un compte utilisateur lors de la première connexion
3. **Intégration Laravel native** : S'intégrer parfaitement avec le système d'authentification Laravel
4. **Facilité d'utilisation** : Fournir un bouton prêt à l'emploi et une configuration simple
5. **Sécurité** : Respecter les meilleures pratiques de sécurité OIDC

---

## 📦 Structure du Package

```
itsme-laravel/
├── src/
│   ├── ItsmeServiceProvider.php           # Service Provider principal
│   ├── Itsme.php                          # Classe principale du package
│   ├── Providers/
│   │   └── ItsmeProvider.php             # Provider Socialite personnalisé
│   ├── Controllers/
│   │   └── ItsmeController.php            # Contrôleur pour les routes
│   ├── Services/
│   │   ├── ItsmeService.php               # Service de gestion Itsme
│   │   └── TokenValidator.php             # Validation des tokens JWT
│   ├── Middleware/
│   │   └── VerifyItsmeState.php           # Middleware pour vérifier le state
│   ├── Models/
│   │   └── ItsmeUser.php                  # Modèle pour les données Itsme
│   └── Exceptions/
│       ├── ItsmeException.php             # Exception de base
│       ├── InvalidTokenException.php      # Exception token invalide
│       └── AuthenticationFailedException.php
├── config/
│   └── itsme.php                          # Fichier de configuration
├── database/
│   └── migrations/
│       └── create_itsme_users_table.php    # Migration pour stocker les données
├── routes/
│   └── web.php                            # Routes du package
├── resources/
│   ├── views/
│   │   ├── itsme-button.blade.php         # Composant bouton
│   │   └── itsme-error.blade.php          # Vue d'erreur
│   └── assets/
│       ├── css/
│       │   └── itsme.css                  # Styles du bouton
│       └── js/
│           └── itsme.js                   # JS si nécessaire
├── tests/
│   ├── Unit/
│   │   ├── ItsmeServiceTest.php
│   │   └── TokenValidatorTest.php
│   └── Feature/
│       ├── AuthenticationTest.php
│       └── CallbackTest.php
├── .gitignore
├── composer.json
├── README.md
├── LICENSE
└── CHANGELOG.md
```

---

## 🔧 Dépendances Requises

### Dépendances PHP
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/socialite": "^5.0",
        "guzzlehttp/guzzle": "^7.0",
        "firebase/php-jwt": "^6.0"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "phpunit/phpunit": "^11.0",
        "mockery/mockery": "^1.6"
    }
}
```

---

## 📝 Étapes de Développement Détaillées

### Phase 1 : Initialisation du Package (Jour 1)

#### 1.1 Création de la structure de base
- [ ] Créer le répertoire du package
- [ ] Initialiser `composer.json` avec les métadonnées
- [ ] Créer la structure de dossiers
- [ ] Configurer `.gitignore`
- [ ] Créer `README.md` de base

#### 1.2 Configuration Composer
- [ ] Définir l'autoload PSR-4
- [ ] Ajouter les dépendances requises
- [ ] Configurer les scripts (tests, analyse)

#### 1.3 Configuration Laravel Package
- [ ] Créer `ItsmeServiceProvider.php`
- [ ] Enregistrer le service provider
- [ ] Publier la configuration
- [ ] Publier les migrations

---

### Phase 2 : Configuration et Service Provider (Jour 2)

#### 2.1 Fichier de Configuration (`config/itsme.php`)
```php
<?php
return [
    'client_id' => env('ITSME_CLIENT_ID'),
    'client_secret' => env('ITSME_CLIENT_SECRET'),
    'redirect' => env('ITSME_REDIRECT_URI', '/itsme/callback'),
    'environment' => env('ITSME_ENVIRONMENT', 'sandbox'), // sandbox|production
    
    // Endpoints (seront découverts via discovery)
    'authorization_endpoint' => env('ITSME_AUTHORIZATION_ENDPOINT'),
    'token_endpoint' => env('ITSME_TOKEN_ENDPOINT'),
    'userinfo_endpoint' => env('ITSME_USERINFO_ENDPOINT'),
    'discovery_url' => env('ITSME_DISCOVERY_URL'),
    
    // Scopes
    'scopes' => [
        'openid',
        'profile',
        'email',
        'phone',
    ],
    
    // Options
    'use_pkce' => env('ITSME_USE_PKCE', true),
    'verify_token_signature' => env('ITSME_VERIFY_TOKEN', true),
];
```

#### 2.2 Service Provider (`src/ItsmeServiceProvider.php`)
- [ ] Enregistrer les services dans le container
- [ ] Publier la configuration
- [ ] Publier les migrations
- [ ] Enregistrer les routes
- [ ] Enregistrer les middlewares
- [ ] Publier les assets (vues, CSS, JS)

#### 2.3 Classe Principale (`src/Itsme.php`)
- [ ] Créer la classe facade
- [ ] Méthodes principales :
  - `redirect()` : Générer l'URL de redirection
  - `user()` : Récupérer l'utilisateur authentifié
  - `getAuthorizationUrl()` : URL d'autorisation
  - `getAccessToken()` : Récupérer le token

---

### Phase 3 : Provider Socialite Personnalisé (Jour 3-4)

#### 3.1 Création du Provider (`src/Providers/ItsmeProvider.php`)
- [ ] Étendre `AbstractProvider` de Socialite
- [ ] Implémenter `getAuthUrl()`
- [ ] Implémenter `getTokenUrl()`
- [ ] Implémenter `getUserByToken()`
- [ ] Implémenter `mapUserToObject()`
- [ ] Gérer la découverte OpenID Connect
- [ ] Implémenter PKCE si activé
- [ ] Gérer le state parameter

#### 3.2 Découverte OpenID Connect
- [ ] Récupérer le document de découverte (`.well-known/openid-configuration`)
- [ ] Parser la configuration
- [ ] Utiliser les endpoints découverts
- [ ] Cache de la configuration

#### 3.3 Gestion PKCE
- [ ] Générer `code_verifier` et `code_challenge`
- [ ] Stocker le `code_verifier` en session
- [ ] Inclure `code_challenge` dans la requête d'autorisation
- [ ] Utiliser `code_verifier` lors de l'échange du token

---

### Phase 4 : Service Itsme (Jour 5)

#### 4.1 Service Principal (`src/Services/ItsmeService.php`)
- [ ] Méthode `getAuthorizationUrl()` :
  - Générer le state
  - Générer PKCE si activé
  - Construire l'URL d'autorisation
  - Stocker le state en session
  
- [ ] Méthode `handleCallback()` :
  - Vérifier le state
  - Échanger le code contre un token
  - Valider les tokens
  - Récupérer les infos utilisateur
  
- [ ] Méthode `getUserInfo()` :
  - Appeler l'endpoint UserInfo
  - Parser la réponse
  - Retourner les données utilisateur

#### 4.2 Validation des Tokens (`src/Services/TokenValidator.php`)
- [ ] Décoder le JWT ID token
- [ ] Vérifier la signature (si activé)
- [ ] Vérifier l'expiration
- [ ] Vérifier l'audience (client_id)
- [ ] Vérifier le nonce
- [ ] Vérifier l'issuer

---

### Phase 5 : Contrôleur et Routes (Jour 6)

#### 5.1 Contrôleur (`src/Controllers/ItsmeController.php`)
- [ ] Méthode `redirect()` :
  - Générer l'URL de redirection
  - Rediriger vers Itsme
  
- [ ] Méthode `callback()` :
  - Vérifier les paramètres de callback
  - Appeler le service pour traiter
  - Gérer les erreurs
  - Créer/connexion de l'utilisateur
  - Rediriger vers la page appropriée

#### 5.2 Routes (`routes/web.php`)
```php
Route::prefix('itsme')->group(function () {
    Route::get('/redirect', [ItsmeController::class, 'redirect'])
        ->name('itsme.redirect');
    
    Route::get('/callback', [ItsmeController::class, 'callback'])
        ->name('itsme.callback');
});
```

#### 5.3 Middleware (`src/Middleware/VerifyItsmeState.php`)
- [ ] Vérifier la présence du state
- [ ] Comparer le state reçu avec celui en session
- [ ] Rejeter si invalide

---

### Phase 6 : Intégration avec Laravel Auth (Jour 7)

#### 6.1 Création/Connexion Utilisateur
- [ ] Rechercher l'utilisateur par `itsme_id` ou `email`
- [ ] Si existe : connecter l'utilisateur
- [ ] Si n'existe pas : créer un nouvel utilisateur
- [ ] Mapper les champs Itsme vers le modèle User Laravel :
  - `sub` → `itsme_id`
  - `email` → `email`
  - `given_name` → `first_name`
  - `family_name` → `last_name`
  - `phone_number` → `phone` (si disponible)

#### 6.2 Migration (`database/migrations/create_itsme_users_table.php`)
```php
Schema::create('itsme_users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('itsme_id')->unique();
    $table->string('email')->nullable();
    $table->json('itsme_data')->nullable();
    $table->timestamps();
});
```

#### 6.3 Modèle (`src/Models/ItsmeUser.php`)
- [ ] Relation avec le modèle User
- [ ] Accessors/Mutators pour les données JSON
- [ ] Méthodes helper

---

### Phase 7 : Interface Utilisateur (Jour 8)

#### 7.1 Composant Bouton (`resources/views/itsme-button.blade.php`)
- [ ] Design conforme aux guidelines Itsme
- [ ] Lien vers la route de redirection
- [ ] Styles CSS intégrés
- [ ] Support des classes Tailwind (optionnel)
- [ ] Version responsive
- [ ] Accessibilité (ARIA labels)

#### 7.2 Styles CSS (`resources/assets/css/itsme.css`)
- [ ] Styles du bouton Itsme
- [ ] États hover/active
- [ ] Responsive design
- [ ] Compatibilité navigateurs

#### 7.3 Vue d'Erreur (`resources/views/itsme-error.blade.php`)
- [ ] Affichage des erreurs d'authentification
- [ ] Messages utilisateur-friendly
- [ ] Lien de retour

---

### Phase 8 : Gestion des Erreurs (Jour 9)

#### 8.1 Exceptions Personnalisées
- [ ] `ItsmeException` (classe de base)
- [ ] `InvalidTokenException`
- [ ] `AuthenticationFailedException`
- [ ] `InvalidStateException`
- [ ] `ConfigurationException`

#### 8.2 Handler d'Erreurs
- [ ] Traduire les erreurs Itsme
- [ ] Logger les erreurs
- [ ] Retourner des messages appropriés

---

### Phase 9 : Tests (Jour 10-11)

#### 9.1 Tests Unitaires
- [ ] `ItsmeServiceTest` :
  - Test génération URL d'autorisation
  - Test gestion callback
  - Test récupération UserInfo
  
- [ ] `TokenValidatorTest` :
  - Test validation token valide
  - Test rejet token expiré
  - Test rejet token invalide
  - Test vérification signature

#### 9.2 Tests Feature
- [ ] `AuthenticationTest` :
  - Test redirection vers Itsme
  - Test callback réussi
  - Test création utilisateur
  - Test connexion utilisateur existant
  
- [ ] `CallbackTest` :
  - Test gestion erreurs
  - Test validation state
  - Test gestion code invalide

#### 9.3 Configuration Testbench
- [ ] Configurer Orchestra Testbench
- [ ] Créer les fixtures de test
- [ ] Mocker les appels HTTP vers Itsme

---

### Phase 10 : Documentation (Jour 12)

#### 10.1 README.md
- [ ] Description du package
- [ ] Installation
- [ ] Configuration
- [ ] Utilisation de base
- [ ] Exemples de code
- [ ] Troubleshooting
- [ ] Changelog

#### 10.2 Documentation Code
- [ ] PHPDoc pour toutes les classes
- [ ] Commentaires pour les méthodes complexes
- [ ] Exemples dans les commentaires

#### 10.3 Guide d'Intégration
- [ ] Étapes d'intégration dans une app Laravel
- [ ] Configuration du portail Itsme
- [ ] Exemples de vues Blade
- [ ] Personnalisation

---

### Phase 11 : Optimisations et Sécurité (Jour 13)

#### 11.1 Sécurité
- [ ] Validation stricte des tokens
- [ ] Protection CSRF (state parameter)
- [ ] Rate limiting sur les routes
- [ ] Chiffrement des données sensibles
- [ ] Validation des redirect URIs

#### 11.2 Performance
- [ ] Cache de la configuration OpenID
- [ ] Cache des tokens (si approprié)
- [ ] Optimisation des requêtes DB

#### 11.3 Logging
- [ ] Logger les tentatives d'authentification
- [ ] Logger les erreurs
- [ ] Logger les événements importants

---

### Phase 12 : Finalisation (Jour 14)

#### 12.1 Validation
- [ ] Tests finaux complets
- [ ] Vérification de la documentation
- [ ] Vérification de la compatibilité Laravel 12
- [ ] Test dans un environnement réel (sandbox)

#### 12.2 Préparation Release
- [ ] Versionner le package
- [ ] Créer le tag Git
- [ ] Préparer pour Packagist
- [ ] Créer les assets de release

---

## 🔐 Configuration Requise

### Variables d'Environnement (.env)
```env
ITSME_CLIENT_ID=your_client_id
ITSME_CLIENT_SECRET=your_client_secret
ITSME_REDIRECT_URI=https://your-app.com/itsme/callback
ITSME_ENVIRONMENT=sandbox
ITSME_USE_PKCE=true
ITSME_VERIFY_TOKEN=true
```

### Configuration Portail Itsme
1. Créer un compte développeur
2. Enregistrer l'application
3. Configurer les redirect URIs autorisés
4. Obtenir Client ID et Client Secret
5. Tester en sandbox

---

## 📊 Mapping des Données Utilisateur

### Claims Itsme → Champs Laravel
```
sub (subject)          → itsme_id (unique identifier)
email                  → email
given_name            → first_name
family_name           → last_name
phone_number          → phone (optionnel)
name                   → name (full name)
```

---

## 🎨 Guidelines Bouton Itsme

### Design Requirements
- Utiliser les couleurs officielles Itsme
- Respecter les dimensions minimales
- Inclure le logo Itsme
- Texte : "Se connecter avec itsme®" ou "S'inscrire avec itsme®"
- Accessibilité : contraste, ARIA labels

### Assets Nécessaires
- Logo Itsme (SVG/PNG)
- Guidelines de marque (disponibles sur le portail développeur)

---

## 🧪 Scénarios de Test

### Tests Fonctionnels
1. **Première connexion** : Création automatique du compte
2. **Connexion existante** : Connexion de l'utilisateur existant
3. **Erreur d'authentification** : Gestion des erreurs Itsme
4. **Token expiré** : Renouvellement automatique
5. **State invalide** : Rejet de la requête

### Tests de Sécurité
1. **CSRF** : Vérification du state parameter
2. **Token forgé** : Rejet des tokens invalides
3. **Replay attack** : Vérification du nonce
4. **Redirect URI** : Validation des URIs autorisés

---

## 📚 Ressources et Références

### Documentation Officielle
- [Portail Développeur Itsme](https://www.itsme-id.com/en-BE/business/developer)
- [Documentation Technique GitHub](https://belgianmobileid.github.io/doc/index)
- [OpenID Connect Specification](https://openid.net/specs/openid-connect-core-1_0.html)

### Packages de Référence
- Laravel Socialite
- Laravel Passport (pour comprendre OAuth2/OIDC)
- spid-laravel (package similaire pour SPID)

---

## ⏱️ Estimation Temporelle

| Phase | Durée | Priorité |
|-------|-------|----------|
| Phase 1 : Initialisation | 1 jour | Haute |
| Phase 2 : Configuration | 1 jour | Haute |
| Phase 3 : Provider Socialite | 2 jours | Haute |
| Phase 4 : Service Itsme | 1 jour | Haute |
| Phase 5 : Contrôleur/Routes | 1 jour | Haute |
| Phase 6 : Intégration Auth | 1 jour | Haute |
| Phase 7 : Interface UI | 1 jour | Moyenne |
| Phase 8 : Gestion Erreurs | 1 jour | Moyenne |
| Phase 9 : Tests | 2 jours | Haute |
| Phase 10 : Documentation | 1 jour | Moyenne |
| Phase 11 : Optimisations | 1 jour | Basse |
| Phase 12 : Finalisation | 1 jour | Haute |
| **TOTAL** | **14 jours** | |

---

## ✅ Checklist de Validation Finale

- [ ] Tous les tests passent
- [ ] Documentation complète
- [ ] Code conforme aux standards PSR
- [ ] Sécurité validée
- [ ] Compatible Laravel 12
- [ ] Testé en sandbox Itsme
- [ ] README complet avec exemples
- [ ] Changelog à jour
- [ ] License définie
- [ ] Package prêt pour Packagist

---

## 🚀 Prochaines Étapes Après Création

1. **Tests en Production** : Tester avec un compte Itsme réel
2. **Feedback Utilisateurs** : Collecter les retours
3. **Améliorations** : Ajouter des fonctionnalités selon les besoins
4. **Support** : Maintenir et mettre à jour le package
5. **Publication** : Publier sur Packagist et GitHub

---

## 📝 Notes Importantes

- **Laravel 12** : S'assurer de la compatibilité avec la dernière version
- **PHP 8.2+** : Utiliser les fonctionnalités modernes de PHP
- **Sécurité** : Toujours valider et vérifier les tokens
- **Tests** : Couverture de code élevée recommandée
- **Documentation** : Essentielle pour l'adoption du package

---

*Ce plan est un guide complet pour le développement du package. Il peut être ajusté selon les besoins spécifiques et les découvertes lors du développement.*

